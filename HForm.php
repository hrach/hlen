<?php

/**
 * Hlen Framework
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */


/**
 * Poskytuje komplexni sluzby kolem formularu
 *
 * Vytvari formularove obejkty 
 * @package   Hlen
 * @author    Jan Skrasek
 * @version   0.1.0
 */
class HForm implements ArrayAccess
{

    /** @var consts */
    const
        EQUAL = 200,
        FILLED = 201,
        NUMERIC = 202,
        MINLENGTH = 203,
        MAXLENGTH = 204,
        LENGTH = 205,
        EMAIL = 206,
        URL = 207;

    /** @var string */
    private $url;

    /** @var array */
    private $rules = array();

    /** @var array */
    private $data = array();

    /** @var array */
    public $submitedData = array();

    /** @var array */
    public $submitedDataComplete = array();

    /** @var HElement */
    private $formElement;

    /** @var string */
    private $method = 'post';

    /** @var array */
    private $errors = array();

    /**
     * Konstruktor
     */
    public function __construct($url = null)
    {
        if (class_exists('HApplication', false)) {
            $this->url = HHttp::getBase();

            if ($url === null) {
                $this->url .= implode('/', HRouter::$url);
            } else {
                $this->url .= HApplication::systemUrl($url);
            }
        } else {
            $this->url = HHttp::getBase() . $url;
        }
    }

    /**
     * Prida input text
     *
     * @param string $id
     * @return HFormElement
     */
    public function addText($id)
    {
        $this->data[$id] = new HFormElementInput('text', $id);
        return $this->data[$id];
    }

    /**
     * Prida input password
     *
     * @param string $id
     * @return HFormElement
     */
    public function addPassword($id)
    {
        $this->data[$id] = new HFormElementInput('password', $id);
        return $this->data[$id];
    }

    /**
     * Prida hidden input
     *
     * @param string $id
     * @return HFormElement
     */
    public function addHidden($id)
    {
        $this->data[$id] = new HFormElementInput('hidden', $id);
        return $this->data[$id];
    }

    /**
     * Prida textarea
     *
     * @param string $id
     * @return HFormElement
     */
    public function addTextArea($id)
    {
        $this->data[$id] = new HFormElementTextArea($id);
        return $this->data[$id];
    }

    /**
     * Prida select
     *
     * @param string  $id
     * @param array   $options
     * @return HFormElement
     */
    public function addSelect($id, $options)
    {
        $this->data[$id] = new HFormElementSelect($id, $options);
        return $this->data[$id];
    }

    /**
     * Prida submit button
     *
     * @param string  $id
     * @param mixed   $value
     * @return HFormElement
     */
    public function addSubmit($id = 'submit')
    {
        $this->data[$id] = new HFormElementInput('submit', $id);
        return $this->data[$id];
    }

    /**
     * Vrati oteviraci tag formulare
     *
     * @return string
     */
    public function start()
    {
        $this->formElement = new HHtml('form');
        $this->formElement['action'] = $this->url;
        $this->formElement['method'] = $this->method;

        return $this->formElement->startTag();
    }

    /**
     * Vrati ukoncovaci tag formulare
     * 
     * @return string
     */
    public function end()
    {
        return $this->formElement->endTag();
    }

    /**
     * Prida pravidlo
     *
     * @param string  $id
     * @param int     $rule
     * @param string  $message
     * @param mixed   $arg = null
     */
    public function addRule($id, $rule, $message, $arg = null)
    {
        $this->data[$id]->addRule($rule, $message, $arg);
    }

    /**
     * Prida podminku
     *
     * @param string  $id
     * @param int     $rule
     * @param mixed   $arg = null
     * @return HFormCondition
     */
    public function addCondition($id, $rule, $arg = null)
    {
        return $this->data[$id]->addCondition($rule, $arg);
    }

    /**
     * Zvaliduje formular
     *
     * @return boolean
     */
    public function validate()
    {
        $return = true;
        foreach ($this->data as $element) {
            if (!$element->validate($this->submitedData, $this)) {
                $return = false;
            }
        }

        return $return;
    }

    /**
     * Prida chybu validace
     *
     * @param string $message
     */
    public function addError($message)
    {
        $this->errors[] = $message;
    }

    /**
     * Vrati seznam chyb
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Zkontroluje, zda byl formualr odeslan
     *
     * @return boolean
     */
    public function isSubmited()
    {
        if (HHttp::getRequestMethod() === 'post') {
            $data = HHttp::getPost();
        } else {
            $data = HHttp::getGet();
        }

        foreach ($data as $id => $val) {
            $data[$id] = trim($val);
        }

        $dataComplete = $data;
        foreach ($this->data as $el) {
            if ($el->isSubmited($data)) {
                foreach($this->data as $element) {
                    if ($data[$element->getId()] == $element->getEmptyValue()) {
                        $data[$element->getId()] = null;
                    }

                    if ($element->getTag() === 'submit') {
                        $dataComplete[$element->getId()] = (bool) $dataComplete[$element->getId()];
                        unset( $data[$element->getId()] );
                    } elseif( $element->getTag() === 'select' ) {
                        if (!$element->has( $data[$element->getId()] )) {
                            unset($data[$element->getId()]);
                            unset($dataComplete[$element->getId()]);
                        }
                    }
                }

                $this->submitedData = $data;
                $this->submitedDataComplete = $dataComplete;
                return true;
            }
        }
        return false;
    }

    /**
     * Vrati data odeslaneho formulare
     *
     * @param boolean $complete = false
     * @return array
     */
    public function getSubmited($complete = false)
    {
        if ($complete) {
            return $this->submitedDataComplete;
        } else {
            return $this->submitedData;
        }
    }

    /**
     * Nastavi vychozi hodnoty
     *
     * @param mixed $defaults
     */
    public function setDefaults($defaults)
    {
        foreach ($defaults as $id => $val) {
            if (is_object($this->data[$id]) && $this->data[$id]->getTag() !== 'submit') {
                $this->data[$id]->setDefault($val);
            }
        }
    }

    /**
     * Nastavi hodnoty, ktere byly odeslany formularem
     */
    public function reSetDefaults()
    {
        foreach ($this->submitedDataComplete as $id => $val) {
            if (is_object($this->data[$id]) && !in_array($this->data[$id]->getTag(), array('submit', 'password'))) {
                $this->data[$id]->setDefault($val);
            }
        }
    }

    /**
     * Vyrenderuje jednoduse taulkove formatovany formular
     */
    public function render()
    {
        $render = "";

        if (!empty($this->errors)) {
            $render .= "<h3>Chyby</h3><ul>";
            foreach ($this->errors as $err) {
                $render .= "<li>" . $err . "</li>";
            }
            $render .= "</ul>";
        }

        $render .= $this->start() . "<table>\n";
        foreach ($this->data as $row) {
            if ($row->getTag() !== 'submit') {
                $render .= "<tr>\n"
                         . '<td>' . $row->label($row->getId()) . "</td>\n"
                         . '<td>' . $row->element . "</td>\n"
                         . "</tr>\n";
            } else {
                $render .= "<tr>\n<td></td>\n"
                         . '<td>' . $row->element(array('value' => $row->getId())) . "</td>\n"
                         . "</tr>\n";
            }
        }
        $render .= "</table>\n" . $this->end();
        return $render;
    }

    /**
     * Pri vypsani obejktu se vyrenderuje formular
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Ulozi do pole formularovych prvku novy objekt
     *
     * @param string $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Vrati z pole formularovych prvku objekt
     *
     * @param string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        if ($this->check($key)) {
            return $this->data[$key];
        }
    }

    /**
     * Odstrani z pole formularovych prvku objekt
     *
     * @param string $key
     */
    public function offsetUnset($key)
    {
        if ($this->check($key)) {
            unset($this->data[$key]);
        }
    }

    /**
     * Zkontroluje v poli formularovych prvku existenci objektu
     *
     * @param string $key
     * @param boolean
     */
    public function offsetExists($key)
    {
        return $this->check($key);
    }

    /**
     * Zkontroluje v poli formularovych prvku existenci objektu
     *
     * @param string $key
     * @return boolean
     */
    private function check($key)
    {
        if (!array_key_exists($key, $this->data)) {
            return false;
        }
        return true;
    }

}


/**
 * Podminkovy objekt
 *
 * Provede validaci jen za splneni podminky
 * @package   Hlen
 * @author    Jan Skrasek
 * @version   0.1.0
 */
class HFormCondition
{

    /** @var int */
    private $rule;
    /** @var $mixed*/
    private $arg;
    /** @var array */
    private $rules = array();


    /**
     * Konstruktor
     *
     * @param int    $rule = null
     * @param mixed  $arg = null
     * @return HFormCondition
     */
    public function __construct($rule = null, $arg = null)
    {
        $this->rule = $rule;
        $this->arg = $arg;

        return $this;
    }

    /**
     * Zvaliduje element
     *
     * Validace probehne jen pokud je podminka splnena
     * @param mixed   $value
     * @param HForm   $form
     * @param string  $emptyValue
     * @return boolean
     */
    public function validate($value, $form, $emptyValue)
    {
        if (!empty($this->rule))
        {
            if (is_object($this->arg)) {
                $this->arg = $form->submitedData[$this->arg->getId()];
            }

            if (!$this->isValid($this->rule, $value, $this->arg, $emptyValue)) {
                return true;
            }
        }

        foreach ($this->rules as $rule) {
            if (is_object($rule['arg'])) {
                $arg = $form->submitedData[$rule['arg']->getId()];
            } else {
                $arg = $rule['arg'];
            }

            if (!$this->isValid($rule['rule'], $value, $arg, $emptyValue)) {
                $form->addError( $rule['message'] );
                return false;
            }
        }

        return true;
    }

    /**
     * Prida pravidlo
     *
     * @param int     $rule
     * @param string  $message
     * @param mixed   $arg = null
     */
    public function addRule($rule, $message, $arg = null)
    {
        $this->rules[] = array(
            'rule' => $rule,
            'message' => $message,
            'arg' => $arg,
        );
    }

    /**
     * Provede konkretni validaci
     *
     * @param integer  $rule
     * @param string   $value
     * @param string   $arg
     * @param string   $emptyValue
     * @return boolean
     */
    private function isValid($rule, $value, $arg, $emptyValue)
    {
        if ($value == $emptyValue) {
            $value = null;
        }

        switch ($rule) {
            case HForm::EQUAL:      return $value == $arg; break;
            case HForm::FILLED:     return !empty($value); break;
            case HForm::EMAIL:      return preg_match('/^[^@]+@[^@]+\.[a-z]{2,6}$/i', $value); break;
            case HForm::URL:        return preg_match('/^.+\.[a-z]{2,6}(\\/.*)?$/i', $value); break;
            case HForm::NUMERIC:    return is_numeric($value); break;
            case HForm::MINLENGTH:  return strlen($value) >= $arg; break;
            case HForm::MAXLENGTH:  return strlen($value) <= $arg; break;
            case HForm::LENGTH:     return strlen($value) == $arg; break;
        }

        return true;
    }

}


/**
 * Objekt pro <input type="text" />
 *
 * Upravuje zakladni chovani HFormElementu, aby odpovidalo potrebam inputu
 * @package   Hlen
 * @author    Jan Skrasek
 * @version   0.1.0
 */
class HFormElementInput extends HFormElement
{

    /**
     * Konstruktor
     *
     * @param string  $type
     * @param string  $id
     */
    public function __construct($type, $id)
    {
        parent::__construct('input', $id);
        $this->element['type'] = $type;
        $this->element['class'] = $type;
    }

    /**
     * Vrati typ tagu
     *
     * @return string
     */
    public function getTag()
    {
        return $this->element['type'];
    }

}


/**
 * Objekt pro <textarea></textarea>
 *
 * Upravuje zakladni chovani HFormElementu, aby odpovidalo potrebam textarea
 * @package   Hlen
 * @author    Jan Skrasek
 * @version   0.1.0
 */
class HFormElementTextArea extends HFormElement
{

    /**
     * Konstruktor
     *
     * @param string $id
     * @param mixed $label
     */
    public function __construct($id)
    {
        parent::__construct('textarea', $id);
    }

    /**
     * Vlozeni obsahu elementu
     *
     * @param string $value
     */
    public function setDefault($value)
    {
        $this->element->setContent($value);
    }

    /**
     * Nastaveni prazdne hodnoty
     *
     * @param string $value
     */
    public function setEmptyValue($value)
    {
        $this->element->setContent($value);
        $this->emptyValue = $value;
    }

}


/**
 * Objekt pro <select></select>
 *
 * Upravuje zakladni chovani HFormElementu, aby odpovidalo potrebam select
 * @package   Hlen
 * @author    Jan Skrasek
 * @version   0.1.0
 */
class HFormElementSelect extends HFormElement
{

    /** @var array */
    private $options = array();


    /**
     * Konstruktor
     *
     * @param string  $id
     * @param array   $options
     */
    public function __construct($id, $options)
    {
        parent::__construct('select', $id);
        $this->createOptionTags($options);
    }

    /**
     * Je hodnota mezi nabizenymi
     *
     * @param string $value
     * @return boolean
     */
    public function has($value)
    {
        return in_array($value, array_keys($this->options));
    }

    /**
     * Nastavi vyhozi hodnotu
     *
     * @param string $value
     */
    public function setDefault($value)
    {
        foreach ($this->options as $option) {
            if ($option['value'] == $value) {
                $option['selected'] = 'selected';
            }
        }
    }

    /**
     * Vrati html elementu
     *
     * @param string $name
     * @return string
     */
    public function __get($name)
    {
        if (empty($name)) {
            throw new LogicException('Cannot read an property without name');
        }

        if ($name === 'element') {
            $this->element->setContent($this->getOptionTags());
        }

        return parent::__get($name);
    }

    /**
     * Vytvori option tagy
     *
     * @param array $options
     */
    private function createOptionTags($options)
    {
        foreach ($options as $key => $val) {
            $option = new HHtml('option');
            $option['value'] = $key;
            $option->setContent($val);

            $this->options[] = $option;
        }
    }

    /**
     * Vrati HTML option tagu
     *
     * @return string
     */
    private function getOptionTags()
    {
        $ret = '';
        foreach ($this->options as $option) {
            $ret .= $option->get();
        }
        return $ret;
    }

}


/**
 * Zakladni trida formularovych elementu
 *
 * @package   Hlen
 * @author    Jan Skrasek
 * @version   0.1.0
 */
class HFormElement
{

    /** @var HHtml */
    protected $label;
    /** @var HHtml */
    protected $element;
    /** @var string */
    protected $emptyValue;
    /** @var array */
    protected $conds = array();


    /**
     * Konstruktor
     *
     * @param string  $tag
     * @param string  $id
     * @param array   $options = null
     */
    function __construct($tag, $id, $options = null)
    {
        $this->tag = $tag;

        $this->label = new HHtml('label');
        $this->label['for'] = 'Form' . HBasics::camelize($id);

        $this->element = new HHtml($tag);
        $this->element['class'] = $tag;
        $this->element['id'] = 'Form' . HBasics::camelize($id);
        $this->element['name'] = $id;
    }

    /**
     * Nastavi atribut
     *
     * @param string  $var
     * @param string  $val
     */
    public function set($var, $val)
    {
        $this->element[$var] = $val;
    }

    /**
     * Nastavi prazdnou hodnotu
     *
     * @param string $value
     */
    public function setEmptyValue($value)
    {
        $this->element['value'] = $value;
        $this->emptyValue = $value;
    }

    /**
     * Vrati prazdnou hodnotu
     *
     * @return string
     */
    public function getEmptyValue()
    {
        return $this->emptyValue;
    }

    /**
     * Je element odeslan
     *
     * @param array $data
     * @return boolean
     */
    public function isSubmited($data)
    {
        $value = $data[$this->getId()];

        if ($value !== $this->getEmptyValue() && !empty($value)) {
            return true;
        }

        return false;
    }

    /**
     * Vrati id
     *
     * @return string
     */
    public function getId()
    {
        return $this->element['name'];
    }

    /**
     * Vrati typ tagu
     *
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Nastavi vychozi hodnotu
     *
     * @param string $value
     */
    public function setDefault($value)
    {
        $this->element['value'] = $value;
    }

    /**
     * Prida podminku
     *
     * @param string  $rule
     * @param mixed   $arg
     * @return HFormCondition
     */
    public function addCondition($rule, $arg)
    {
        return $this->conds[] = new HFormCondition($rule, $arg);
    }

    /**
     * Prida pravidlo
     *
     * @param integer  $rule
     * @param string   $message
     * @param mixed    $arg
     */
    public function addRule($rule, $message, $arg)
    {
        $cond = new HFormCondition();
        $cond->addRule($rule, $message, $arg);

        $this->conds[] = $cond;
    }

    /**
     * Zvaliduje element
     *
     * @param array   $data
     * @param HForm   $form
     * @return boolean
     */
    public function validate($data, $form)
    {
        $value = $data[$this->getId()];

        foreach ($this->conds as $cond) {
            if (!$cond->validate($value, $form, $this->getEmptyValue())) {
                return false;
            }
        }
        return true;
    }

    /**
     * Vrati html pro element a label
     *
     * @param string $name
     * @return string
     */
    public function __get($name)
    {
        if ($name === 'element') {
            return $this->element->get();
        }
    }

    /**
     * Vrati html label
     *
     * @param string  $label
     * @param array   $attributs
     * @return string
     */
    public function label($label, $attributs = array())
    {
        $this->label->setContent($label);
        foreach ($attributs as $key => $val) {
            $this->label[$key] = $val;
        }

        return $this->label->get();
    }

    /**
     * Vrati html element
     *
     * @param array $attributs
     * @return string
     */
    public function element($attributs = array())
    {
        foreach ($attributs as $key => $val) {
            $this->element[$key] = $val;
        }

        return $this->element->get();
    }

}