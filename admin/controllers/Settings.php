<?php

/**
 * This class registers some handlers for Invoicing & Payment settings
 *
 * @package     Nails
 * @subpackage  module-captcha
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Captcha;

use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Admin\Controller\Base;

class Settings extends Base
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        $oNavGroup = Factory::factory('Nav', 'nailsapp/module-admin');
        $oNavGroup->setLabel('Settings');
        $oNavGroup->setIcon('fa-wrench');

        if (userHasPermission('admin:captcha:settings:*')) {
            $oNavGroup->addAction('Captcha');
        }

        return $oNavGroup;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of permissions which can be configured for the user
     * @return array
     */
    public static function permissions()
    {
        $aPermissions = parent::permissions();

        $aPermissions['driver'] = 'Can update driver settings';

        return $aPermissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Manage Captcha settings
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin:captcha:settings:*')) {
            unauthorised();
        }

        $oDb                 = Factory::service('Database');
        $oAppSettingModel    = Factory::model('AppSetting');
        $oCaptchaDriverModel = Factory::model('CaptchaDriver', 'nailsapp/module-captcha');

        //  Process POST
        if ($this->input->post()) {

            //  Settings keys
            $sKeyCaptchaDriver = $oCaptchaDriverModel->getSettingKey();

            //  Validation
            $oFormValidation = Factory::service('FormValidation');

            $oFormValidation->set_rules($sKeyCaptchaDriver, '', '');

            if ($oFormValidation->run()) {

                try {

                    $oDb->trans_begin();

                    //  Drivers
                    $oCaptchaDriverModel->saveEnabled($this->input->post($sKeyCaptchaDriver));

                    $oDb->trans_commit();
                    $this->data['success'] = 'Captcha settings were saved.';

                } catch (\Exception $e) {

                    $oDb->trans_rollback();
                    $this->data['error'] = 'There was a problem saving settings. ' . $e->getMessage();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Get data
        $this->data['settings']                = appSetting(null, 'nailsapp/module-captcha', true);
        $this->data['captcha_drivers']         = $oCaptchaDriverModel->getAll();
        $this->data['captcha_drivers_enabled'] = $oCaptchaDriverModel->getEnabledSlug();

        Helper::loadView('index');
    }
}