<?php
class Dvelum_Backend_PasswordRecovery_Installer extends Externals_Installer
{
    /**
     * Install
     * @param Config_Abstract $applicationConfig
     * @param Config_Abstract $moduleConfig
     * @return boolean
     */
    public function install(Config_Abstract $applicationConfig, Config_Abstract $moduleConfig)
    {
        Lang::addDictionaryLoader('dvelum_recovery', $applicationConfig->get('language').'/dvelum_recovery.php', Config::File_Array);

        $userId = User::getInstance()->getId();
        $lang = Lang::lang('dvelum_recovery');

        $pagesModel = Model::factory('Page');
        $pageItem = $pagesModel->getList(false,['func_code' => 'dvelum_password_recovery']);
        if(empty($pageItem)) {
            try {
                $articlesPage = new Db_Object('Page');
                $articlesPage->setValues(array(
                    'code' => 'recovery',
                    'is_fixed' => 1,
                    'html_title' => $lang->get('password_recovery'),
                    'menu_title' => $lang->get('password_recovery'),
                    'page_title' => $lang->get('password_recovery'),
                    'meta_keywords' => '',
                    'meta_description' => '',
                    'parent_id' => null,
                    'text' => '',
                    'func_code' => 'dvelum_password_recovery',
                    'order_no' => 1,
                    'show_blocks' => true,
                    'published' => false,
                    'published_version' => 0,
                    'editor_id' => $userId,
                    'date_created' => date('Y-m-d H:i:s'),
                    'date_updated' => date('Y-m-d H:i:s'),
                    'author_id' => $userId,
                    'blocks' => '',
                    'theme' => 'default',
                    'date_published' => date('Y-m-d H:i:s'),
                    'in_site_map' => true,
                    'default_blocks' => true
                ));

                if (!$articlesPage->saveVersion(true, false)) {
                    throw new Exception('Cannot create password recovery page');
                }

                if (!$articlesPage->publish()) {
                    throw new Exception('Cannot publish password recovery page');
                }

            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
                return false;
            }
        }

        return true;
    }

    /**
     * Uninstall
     * @param Config_Abstract $applicationConfig
     * @param Config_Abstract $moduleConfig
     * @return boolean
     */
    public function uninstall(Config_Abstract $applicationConfig, Config_Abstract $moduleConfig)
    {
        $pagesModel = Model::factory('Page');
        $pageItems = $pagesModel->getList(false,['func_code' => 'dvelum_recovery']);

        foreach($pageItems as $item)
        {
            try{
                $page = Db_Object::factory('Page', $item['id']);
                $page->unpublish();
            }catch (Exception $e){
                $this->errors[] = $e->getMessage();
                return false;
            }
        }
    }
}