<?php

/**
 * Mapper class that makes a translation between OpenSocial and COIN fieldnames. 
 * @author ivo
 */
class EngineBlock_SocialData_FieldMapper
{
    /**
     * Mapping of COIN ldap keys to open social field names.
     * Some fields may map to multiple open social fields
     * e.g. both displayName and nickName in OpenSocial
     * are based on display name in COIN ldap.
     * @var array
     */
    protected $_l2oMap = array(
        "collabpersonid" => "id" , 
        "displayname" => array(
            "displayName",
            "nickname"
        ) ,
        "mail"      => "emails" ,
        "givenname" => "name"
    );
    
    /**
     * Mapping of open social field names to COIN ldap keys
     * Must contain bare key/value pairs (doesn't support
     * multiple fields like _l2oMap.)
     * @var array
     */
    protected $_o2lMap = array(
        "id"            => "collabpersonid" ,
        "nickname"      => "displayname" ,
        "displayName"   => "displayname" ,
        "emails"        => "mail" ,
        "name"          => "givenname"
    );

    /**
     * Mapping of OpenSocial fieldnames to Grouper field names.
     *
     * @var array
     */
    protected $_o2gMap = array(
        'id'    => 'name',
        'title' => 'displayExtension',
    );

    /**
     * Mapping of Grouper field names to OpenSocial keys.
     *
     * @var array
     */
    protected $_g2oMap = array(
        'name'              => 'id',
        'displayExtension'  => 'title',
    );

    /**
     * A list of OpenSocial fields that are allowed to have multiple values.
     * @var array
     */
    protected $_oMultiValueAttributes = array(
        'emails'
    );

    /**
     * Returns a list of COIN ldap attributes based on a list of 
     * OpenSocial attributes.
     * 
     * If a social attribute is passed that has no COIN ldap counterpart,
     * it will not be converted and will be present in the output as-is.
     * 
     * Mind that the mapper is case sensitive.
     * @todo do we need this case sensitivity?
     * 
     * @param array $socialAttrs An array of OpenSocial attribute names
     * @return array The list of ldap attributes
     */
    public function socialToLdapAttributes($socialAttrs)
    {
        $result = array();
        foreach ($socialAttrs as $socialAttribute) {
            if (isset($this->_o2lMap[$socialAttribute])) {
                if (!in_array($this->_o2lMap[$socialAttribute], $result)) {
                    $result[] = $this->_o2lMap[$socialAttribute];
                }
            } else {
                // We have no mapping for this social field, use the key as-is (useful if userregistry contains
                // custom stuff)
                if (!in_array($socialAttribute, $result)) {
                    $result[] = $socialAttribute;
                }
            }
        }
        return $result;
    }

    /**
     * Convert a COIN ldap record to an opensocial record.
     * 
     * This method creates an opensocial record based on a COIN ldap record.
     * Mind you that the number of keys in the input and in the output might
     * be different, since the mapper can construct multiple opensocial
     * fields based on single values in coin ldap (eg displayname in ldap is
     * used for both displayname and nickname in opensocial.
     * 
     * The method has awareness of which fields in open social are single
     * and which are multivalue, and will make sure that in the return value
     * this is properly reflected.
     * 
     * It's possible to pass a list of socialAttrs you are interested in. If
     * this parameter is non-empty, only the social attributes present in the
     * array will be present in the output. (unknown keys will be silently
     * ignored). 
     *  
     * @param array $data The record to convert. Keys should be ldap 
     *                    attributes.
     * @param array $socialAttrs The list of social attributes that you are
     *                     interested in. If omited or empty array, will try
     *                     to get all fields.
     * @return array An array containing social key/value pairs                  
     */
    public function ldapToSocialData($data, $socialAttrs = array())
    {
        $result = array();
        if (count($socialAttrs)) {
            foreach ($socialAttrs as $socialAttribute) {
                if (isset($this->_o2lMap[$socialAttribute])) {
                    $ldapAttr = $this->_o2lMap[$socialAttribute];
                    if (isset($data[$ldapAttr])) {
                        $result[$socialAttribute] = $this->_pack($data[$ldapAttr], $socialAttribute);
                    } else {    // if there's no opensocial equivalent for this field
                    // assume this is stuff we're not allowed to share
                    // so do not include it in the result.
                    }
                }
            }
        } else {
            foreach ($data as $ldapAttr => $value) {
                if (isset($this->_l2oMap[$ldapAttr])) {
                    if (is_array($this->_l2oMap[$ldapAttr])) {
                        foreach ($this->_l2oMap[$ldapAttr] as $socialAttribute) {
                            $result[$socialAttribute] = $this->_pack($value, $socialAttribute);
                        }
                    } else {
                        $result[$this->_l2oMap[$ldapAttr]] = $this->_pack($value, $socialAttribute);
                    }
                } else {    // ignore value
                }
            }
        }
        return $result;
    }

    /**
     * Convert a Grouper (group) array to an OpenSocial array.
     *
     * @param  $group Group record
     * @return array OpenSocial record
     */
    public function grouperToSocialData($group)
    {
        $result = array();
        foreach ($group as $grouperAttribute => $value) {
            if (isset($this->_g2oMap[$grouperAttribute])) {
                $openSocialKey = $this->_g2oMap[$grouperAttribute];
                $result[$openSocialKey] = $value;
            }
            else {
                // Ignore values not present in the mapping
            }
        }
        return $result;
    }

    /**
     * Converts a value to either an array or a single value, 
     * depending on whether the socialAttr passed is a multivalue
     * key.
     * @param mixed $value A single value or an array of values
     * @param String $socialAttr The name of the social attribute that $value 
     *                           is representing.
     */
    protected function _pack($value, $socialAttr)
    {
        if (in_array($socialAttr, $this->_oMultiValueAttributes)) {
            if (is_array($value)) {
                return $value;
            } else {
                return array(
                    
                    $value
                );
            }
        } else {
            if (is_array($value)) {
                return $value[0];
            } else {
                return $value;
            }
        }
    }
}
