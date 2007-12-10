<?php

class HApplicationController extends Controller
{

    public function error($exception)
    {
        if (get_parent_class($exception) === 'DibiException') {
            $code = 'sql';
        } else {
            $code = $exception->getCode();
        }

        $arg = func_get_args();
        $this->set('arg', $arg);

        if (HConfigure::read('Core.debug') < 2) {
            $this->view = '404';
        } else {
            $this->view = $code;
        }
    }

}