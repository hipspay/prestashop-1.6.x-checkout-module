<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ParentOrderController extends ParentOrderControllerCore
{
    public function init()
    {
        $hips_filename = 'validation';
        include_once(_PS_MODULE_DIR_ . '/hipscheckout/hipscheckout.php');
        $hips = new HipsCheckout();
        if ($hips->active) {
            $link = $hips->getValidationLink($hips_filename);
            Header("Status: 301 Moved Permanently");
            Header("HTTP/1.1 301 Moved Permanently");
            Header("Location: " . $link);
            exit;
        }

        parent::init();
    }
}
