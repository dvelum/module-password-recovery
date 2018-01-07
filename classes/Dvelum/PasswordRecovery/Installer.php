<?php

namespace Dvelum\PasswordRecovery;

use Dvelum\Config\ConfigInterface;
use Dvelum\App\Session\User;
use Dvelum\Orm\Model;
use Dvelum\Orm\Record;
use Dvelum\Lang;
use Dvelum\Config;

class Installer extends \Externals_Installer
{
    /**
     * Install
     * @param ConfigInterface $applicationConfig
     * @param ConfigInterface $moduleConfig
     * @return boolean
     */
    public function install(ConfigInterface $applicationConfig, ConfigInterface $moduleConfig)
    {
        $name = 'dvelum_recovery';
        $src = $applicationConfig->get('language') . '/dvelum_recovery.php';
        $type = Config\Factory::File_Array;
        Lang::addDictionaryLoader($name, $src, $type);

        $userId = User::getInstance()->getId();
        $lang = Lang::lang('dvelum_recovery');

        $pagesModel = Model::factory('Page');
        $pageItem = $pagesModel->query()->filters(['func_code' => 'dvelum_password_recovery'])->getCount();
        if (!$pageItem) {
            try {
                $articlesPage = Record::factory('Page');
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

                if (!$articlesPage->saveVersion()) {
                    throw new \Exception('Cannot create password recovery page');
                }

                if (!$articlesPage->publish()) {
                    throw new \Exception('Cannot publish password recovery page');
                }

            } catch (\Exception $e) {
                $this->errors[] = $e->getMessage();
                return false;
            }
        }
        return true;
    }

    /**
     * Uninstall
     * @param ConfigInterface $applicationConfig
     * @param ConfigInterface $moduleConfig
     * @return boolean
     */
    public function uninstall(ConfigInterface $applicationConfig, ConfigInterface $moduleConfig)
    {
        $pagesModel = Model::factory('Page');
        $pageItems = $pagesModel->query()->filters(['func_code' => 'dvelum_recovery'])->fetchAll();

        foreach ($pageItems as $item) {
            try {
                $page = Record::factory('Page', $item['id']);
                $page->unpublish();
            } catch (\Exception $e) {
                $this->errors[] = $e->getMessage();
                return false;
            }
        }
    }
}