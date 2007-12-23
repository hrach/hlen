<?php

class HApplicationSystemController extends Controller
{

    public function error($exception)
    {
        if (HConfigure::read('Core.debug') < 2) {
            $this->view = '404';
            return null;
        }

        if (get_parent_class($exception) === 'DibiException') {
            $code = 'sql';
        } else {
            switch ($exception->getCode()) {
                case 1000: $code = 'routing'; break;
                case 1001: $code = 'controller'; break;
                case 1002: $code = 'method'; break;
                case 1003: $code = 'view'; break;
                default: $code = $exception->getCode(); break;
            }
        }

        $this->set('message', $exception->getMessage());
        $this->view = $code;

        $arg = func_get_args();
        $this->set('arg', $arg);
    }

}