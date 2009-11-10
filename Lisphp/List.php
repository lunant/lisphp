<?php
require_once 'Lisphp/Form.php';
require_once 'Lisphp/Scope.php';
require_once 'Lisphp/Applicable.php';

class Lisphp_List extends ArrayObject implements Lisphp_Form {
    function evaluate(Lisphp_Scope $scope) {
        $function = $this->car()->evaluate($scope);
        $applicable = $function instanceof Lisphp_Applicable;
        if ($invokable = version_compare(phpversion(), '5.3.0', '>=')) {
            $invokable = method_exists($function, '__invoke');
        }
        if ($invokable) {   
            $parameters = array();
            foreach ($this->cdr() as $arg) {
                $parameters[] = $arg->evaluate($scope);
            }
            return call_user_func_array($function, $parameters);
        }
        if ($applicable) return $function->apply($scope, $this->cdr());
        throw new InvalidApplicationException($function);
    }

    function car() {
        return $this[0];
    }

    function cdr() {
        return new self(array_slice($this->getArrayCopy(), 1));
    }

    function __toString() {
        foreach ($this as $form) {
            if ($form instanceof Lisphp_Form) {
                $strs[] = $form->__toString();
            } else {
                $strs[] = '...';
            }
        }
        return '(' . join(' ', $strs) . ')';
    }
}

class InvalidApplicationException extends BadFunctionCallException {
    public $valueToApply;

    function __construct($valueToApply) {
        $this->valueToApply = $valueToApply;
        $type = is_object($this->valueToApply)
              ? get_class($this->valueToApply)
              : (is_null($this->valueToApply) ? 'nil'
                                              : gettype($this->valueToApply));
        $msg = "$type cannot be applied; see Lisphp_Applicable interface";
        parent::__construct($msg);
    }
}