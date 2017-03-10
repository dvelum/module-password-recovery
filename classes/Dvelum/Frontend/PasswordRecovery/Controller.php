<?php

class Dvelum_Frontend_PasswordRecovery_Controller extends Frontend_Controller
{
    /**
     * @var Config_Abstract
     */
    protected $passwordConfig;
    /**
     * @var Lang
     */
    protected $moduleLang;

    public function __construct()
    {
        parent::__construct();
        $this->passwordConfig = Config::storage()->get('dvelum_recovery.php');
        Lang::addDictionaryLoader('dvelum_recovery', $this->_configMain->get('language').'/dvelum_recovery.php', Config::File_Array);
        $this->moduleLang = Lang::lang('dvelum_recovery');
    }

    /**
     * Recovery form
     */
    public function indexAction()
    {
        $curUrl = $this->_router->findUrl('dvelum_password_recovery');
        $template = new Template();
        $template->disableCache();
        $template->setData([
            'page'=> $this->_page,
            'resource' => $this->_resource,
            'lang' => $this->moduleLang,
            'formUrl'=>Request::url(array($curUrl, 'verify'))
        ]);
        $this->_page->text = $template->render($this->passwordConfig->get('reminder_tpl'));
    }

    /**
     * Проверка введеного имейла и отправка письма с кодом активации
     */
    public function verifyAction()
    {
        $email = Request::post('email', Filter::FILTER_EMAIL, false);

        if (!$email || !Validator_Email::validate($email)) {
            Response::jsonError($this->moduleLang->get('email_invalid'));
        }

        $model = Model::factory('User');

        $userIds = $model->getList(
            ['limit' => 1],
            [
                'email' => $email,
                'enabled'=> true
            ],
            ['id']
        );

        if (count($userIds) == 0) {
            Response::jsonError($this->moduleLang->get('email_user_unknown'));
        }

        $userId = $userIds[0]['id'];

        $user = Db_Object::factory('User', $userId);
        $authCode = Utils::hash(uniqid(time(),true));

        $confDate = new DateTime('now');
        $confDate = $confDate->add(new DateInterval('P1D'))->format('Y-m-d H:i:s');

        try{
            $user->setValues(array(
                'confirmation_code' => $authCode,
                'confirmation_date' => $confDate
            ));
            if(!$user->save())
                throw new Exception('Cannot update user info');
        }catch (Exception $e){
            Response::jsonError($this->_lang->get('CANT_EXEC'));
        }

        $this->sendEmail($user);

        Response::jsonSuccess(array(
            'msg' => $this->moduleLang->get('pwd_success')
        ));
    }

    /**
     * Update password form
     */
    public function renewalAction()
    {
        $template = new Template();
        $code = Request::get('c', Filter::FILTER_ALPHANUM, false);

        $user = $this->_findUserByCode($code);

        $url = $this->_router->findUrl('dvelum_password_recovery');
        $template->setData([
            'form' => true,
            'page'=> $this->_page,
            'resource' => $this->_resource,
            'lang' => $this->moduleLang,
            'formUrl'=>Request::url([$url, 'verify']),
            'confirmUrl'=>Request::url([$url, 'confirm']),
            'passUrl' => Request::url([$url]),
            'code'=> $code
        ]);

        if (!$user instanceof Db_Object) {
            $template->set('form', false);
            $template->set('error', $this->moduleLang->get(strval($user)));
            $this->_page->text = $template->render($this->passwordConfig->get('renewal_tpl'));
            return;
        }

        $this->_page->text = $template->render($this->passwordConfig->get('renewal_tpl'));
    }

    /**
     * Find user by activation code
     * @param $code
     * @return Db_Object|string (Object | error string)
     */
    protected function _findUserByCode($code)
    {
        if (!$code) {
            return 'pwd_confirm_invalid';
        }

        $model = Model::factory('user');
        // backward compatibility (not unique field)
        $item = $model->getList(false,['confirmation_code'=>$code]);

        if(count($item) !==1){
            return 'pwd_confirm_invalid';
        }

        $found = $item[0];

        if (strtotime($found['confirmation_date']) < time()) {
            return 'pwd_code_expired';
        }

        return Db_Object::factory('User', $found['id']);
    }

    /**
     * Send activation code
     * @param Db_Object $user
     */
    protected function sendEmail(Db_Object $user)
    {
        $configMail = Config::storage()->get('mail.php')->get('forgot_password');

        $userData = $user->getData();
        $confDate = new DateTime($userData['confirmation_date']);

        $url = $this->_router->findUrl('dvelum_password_recovery');

        Request::isHttps() ? $scheme='https': $scheme ='http://';

        $template = new Template();
        $template->setProperties(array(
            'name' => $userData['name'],
            'email' => $userData['email'],
            'confirmation_code' => $userData['confirmation_code'],
            'confirmation_date' => $confDate->format('d.m.Y H:i'),
            'url' => $scheme . Request::server('HTTP_HOST', Filter::FILTER_URL, '') . Request::url(array($url, 'renewal'))
        ));

        $templatePath = $configMail['template'][$this->_configMain->get('language')];
        $mailText = $template->render($templatePath);

        $mail = new Zend_Mail('UTF-8');
        $mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64)
            ->setSubject($configMail['subject'])
            ->setFrom($configMail['fromAddress'], $configMail['fromName'])
            ->addTo($userData['email'], $userData['name'])
            ->setBodyHtml($mailText, 'utf-8');

        $mail->send(new Zend_Mail_Transport_Sendmail());
    }

    /**
     * Password confirm
     */
    public function confirmAction()
    {
        $newPassword = Request::post('new_password', Filter::FILTER_ALPHANUM, false);
        $newPasswordConfirm = Request::post('new_password_confirm', Filter::FILTER_ALPHANUM, false);
        $code = Request::post('code', Filter::FILTER_ALPHANUM, false);

        if (!$newPassword || !$newPasswordConfirm || !$code) {
            Response::jsonError($this->_lang->get('FILL_FORM'));
        }

        if (!Validator_Password::validate($newPassword)) {
            Response::jsonError($this->moduleLang->get('pwd_invalid'));
        }

        if ($newPassword !== $newPasswordConfirm) {
            Response::jsonError($this->moduleLang->get('pwd_mismatch'));
        }


        $user = $this->_findUserByCode($code);

        if (!$user instanceof Db_Object) {
            Response::jsonError($this->_lang->get(strval($user)));
        }

        $user->setValues(array(
            'pass' => password_hash($newPassword , PASSWORD_DEFAULT),
            'confirmation_date' => date('Y-m-d H:i:s')
        ));

        if ($user->save() && User::login($user->get('login'), $newPassword)) {
            Response::jsonSuccess(array(
                'msg' => $this->moduleLang->get('pwd_renewal_success')
            ));
        } else {
            Response::jsonError($this->moduleLang->get('pwd_failure'));
        }
    }
}