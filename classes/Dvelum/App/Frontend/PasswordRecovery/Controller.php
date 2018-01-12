<?php

namespace Dvelum\App\Frontend\PasswordRecovery;

use Dvelum\App\Auth;
use Dvelum\App\Frontend;
use Dvelum\Config\ConfigInterface;
use Dvelum\Lang;
use Dvelum\Config;
use Dvelum\Orm\RecordInterface;
use Dvelum\Request;
use Dvelum\Response;
use Dvelum\Service;
use Dvelum\View;
use Dvelum\Filter;
use Dvelum\Orm\Model;
use Dvelum\Orm\Record;
use Dvelum\Utils;

class Controller extends Frontend\Controller
{
    /**
     * @var ConfigInterface
     */
    protected $passwordConfig;
    /**
     * @var Lang
     */
    protected $moduleLang;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->passwordConfig = Config::storage()->get('dvelum_recovery.php');
        Lang::addDictionaryLoader('dvelum_recovery', $this->appConfig->get('language').'/dvelum_recovery.php', Config\Factory::File_Array);
        $this->moduleLang = Lang::lang('dvelum_recovery');
    }

    /**
     * Recovery form
     */
    public function indexAction()
    {
        $page = \Page::getInstance();

        $curUrl = $this->router->findUrl('dvelum_password_recovery');
        $template = new View();
        $template->disableCache();
        $template->setData([
            'page'=> $page,
            'resource' => $this->resource,
            'lang' => $this->moduleLang,
            'formUrl'=>$this->request->url([$curUrl, 'verify'])
        ]);
        $page->text = $template->render($this->passwordConfig->get('reminder_tpl'));
    }

    /**
     * Check entered email and activation code
     */
    public function verifyAction()
    {
        $email = $this->request->post('email', Filter::FILTER_EMAIL, false);

        if (!$email || !\Validator_Email::validate($email)) {
            $this->response->error($this->moduleLang->get('email_invalid'));
            return;
        }

        $model = Model::factory('User');

        $userId = $model->query()
            ->params(['limit' => 1])
            ->filters([
                'email' => $email,
                'enabled'=> true
            ])
            ->fields(['id'])->fetchOne();

        if (empty($userId)) {
            $this->response->error($this->moduleLang->get('email_user_unknown'));
            return;
        }

        $authCode = Utils::hash(uniqid(time(),true));

        $confDate = new \DateTime('now');
        $confDate = $confDate->add(new \DateInterval('P1D'))->format('Y-m-d H:i:s');

        try{
            $user = Record::factory('User', $userId);
            $user->setValues([
                'confirmation_code' => $authCode,
                'confirmation_date' => $confDate
            ]);

            if(!$user->save()){
                throw new \Exception('Cannot save user');
            }

            $this->sendEmail($user);

        }catch (\Exception $e){
            Model::factory('User')->logError(get_called_class().' '.$e->getMessage());
            $this->response->error($this->lang->get('CANT_EXEC'));
            return;
        }

        $this->response->success([
            'msg' => $this->moduleLang->get('pwd_success')
        ]);
    }

    /**
     * Update password form
     */
    public function renewalAction()
    {
        $template = new View();
        $code = $this->request->get('c', Filter::FILTER_ALPHANUM, false);

        $user = $this->findUserByCode($code);
        $page = \Page::getInstance();

        $url = $this->router->findUrl('dvelum_password_recovery');
        $template->setData([
            'form' => true,
            'page'=> $page,
            'resource' => $this->resource,
            'lang' => $this->moduleLang,
            'formUrl'=>$this->request->url([$url, 'verify']),
            'confirmUrl'=>$this->request->url([$url, 'confirm']),
            'passUrl' => $this->request->url([$url]),
            'code'=> $code
        ]);

        if (!$user instanceof RecordInterface) {
            $template->set('form', false);
            $template->set('error', $this->moduleLang->get(strval($user)));
            $page->text = $template->render($this->passwordConfig->get('renewal_tpl'));
            return;
        }
        $page->text = $template->render($this->passwordConfig->get('renewal_tpl'));
    }

    /**
     * Find user by activation code
     * @param $code
     * @return RecordInterface | string
     */
    protected function findUserByCode($code)
    {
        if (!$code) {
            return 'pwd_confirm_invalid';
        }

        $model = Model::factory('user');
        // backward compatibility (not unique field)
        $item = $model->query()->filters(['confirmation_code'=>$code])->fetchAll();

        if(count($item) !==1){
            return 'pwd_confirm_invalid';
        }

        $found = $item[0];

        if (strtotime($found['confirmation_date']) < time()) {
            return 'pwd_code_expired';
        }

        return Record::factory('User', $found['id']);
    }

    /**
     * Send activation code
     * @param RecordInterface $user
     */
    protected function sendEmail(RecordInterface $user)
    {
        $configMail = Config::storage()->get('mail.php')->get('forgot_password');

        $userData = $user->getData();
        $confDate = new \DateTime($userData['confirmation_date']);

        $url = $this->router->findUrl('dvelum_password_recovery');

        $this->request->isHttps() ? $scheme='https': $scheme ='http://';

        $template = new View();
        $template->setProperties(array(
            'name' => $userData['name'],
            'email' => $userData['email'],
            'confirmation_code' => $userData['confirmation_code'],
            'confirmation_date' => $confDate->format('d.m.Y H:i'),
            'url' => $scheme . $this->request->server('HTTP_HOST', Filter::FILTER_URL, '') .$this->request->url([$url, 'renewal'])
        ));

        $templatePath = $configMail['template'][$this->appConfig->get('language')];
        $mailText = $template->render($templatePath);

        $mail = new \Zend\Mail\Message();
        $mail->setEncoding('UTF-8');
        $mail->setSubject($configMail['subject'])
            ->setFrom($configMail['fromAddress'], $configMail['fromName'])
            ->addTo($userData['email'], $userData['name'])
            ->setBody($mailText);

        $transport = Service::get('MailTransport');
        $transport->send($mail);
    }

    /**
     * Password confirm
     */
    public function confirmAction()
    {
        $newPassword = $this->request->post('new_password', Filter::FILTER_ALPHANUM, false);
        $newPasswordConfirm = $this->request->post('new_password_confirm', Filter::FILTER_ALPHANUM, false);
        $code = $this->request->post('code', Filter::FILTER_ALPHANUM, false);

        if (!$newPassword || !$newPasswordConfirm || !$code) {
            $this->response->error($this->lang->get('FILL_FORM'));
            return;
        }

        if (!\Validator_Password::validate($newPassword)) {
            $this->response->error($this->moduleLang->get('pwd_invalid'));
            return;
        }

        if ($newPassword !== $newPasswordConfirm) {
            $this->response->error($this->moduleLang->get('pwd_mismatch'));
            return;
        }

        $user = $this->findUserByCode($code);

        if (!$user instanceof RecordInterface) {
            $this->response->error($this->moduleLang->get(strval($user)));
            return ;
        }

        try{
            $user->setValues([
                'pass' => \password_hash($newPassword , PASSWORD_DEFAULT),
                'confirmation_date' => date('Y-m-d H:i:s'),
                'confirmed'=>1
            ]);
            if(!$user->save()){
                throw new \Exception('Cannot save user');
            }

            $auth = new Auth($this->request, $this->appConfig);
            $auth->login($user->get('login'), $newPassword, $this->appConfig->get('default_auth_provider'));

            $this->response->success([
                'msg' => $this->moduleLang->get('pwd_renewal_success')
            ]);

        }catch (\Exception $e){
            $this->response->error($this->moduleLang->get('pwd_failure'));
            return;
        }
    }
}