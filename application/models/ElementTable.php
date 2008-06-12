<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2007-2008
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package Omeka
 **/
 
/**
 * @package Omeka
 * @author CHNM
 * @copyright Center for History and New Media, 2007-2008
 **/
class ElementTable extends Omeka_Db_Table
{
    /**
     * Retrieve a set of Element records for the item. This set of records
     * will be indexed by the name of the element.
     * 
     * @param integer ID of the item
     * @return array
     **/
    public function findByItem($id)
    {
        $select = $this->getSelectForItem($id);
        $objs = $this->fetchObjects($select);
        return $this->indexRecordsByName($objs);
    }
    
    /**
     * Overriding getSelect() to always return the type_name and type_regex
     * for retrieved elements.
     * 
     * @return Omeka_Db_Select
     **/
    public function getSelect()
    {
        $select = parent::getSelect();
        $db = $this->getDb();
        $select->joinLeft(array('et'=>$db->ElementType), 'et.id = e.element_type_id', 
            array('type_name'=>'et.name', 'type_regex'=>'et.regular_expression'));
        return $select;
    }
    
    public function getSelectForItem($itemId)
    {
        $select = $this->getSelect();
        $db = $this->getDb();
        
        $select->joinInner(array('il' => $db->ItemsElements), 
                           'il.element_id = e.id', 
                           array('il.text'));
        $select->joinInner(array('i' => $db->Item), 
                           'il.item_id = i.id', 
                           array());
        
        $select->where('i.id = ?', $itemId);
        
        return $select;       
    }
    
    /**
     * Index a set of Elements based on their name.
     * 
     * @param array
     * @return array
     **/
    protected function indexRecordsByName(array $objs)
    {
        $indexed = array();
        foreach($objs as $obj) {
            $indexed[$obj->name][] = $obj;
        }
        
        return $indexed;        
    }
    
    /**
     * Retrieve the names of all the elements for a given Item Type
     * 
     * @see item_type_elements()
     * @param integer
     * @return array
     **/
    public function findNamesByItemType($itemTypeId)
    {
        //Retrieve dummy data
        return array('Physical Dimensions', 'Original Transcript');
    }
    
    /**
     * Return the element's name and id for <select> tags on it.
     * 
     * @see Omeka_Db_Table::findPairsForSelectForm()
     * @param string
     * @return void
     **/
    protected function _getColumnPairs()
    {
        return array('e.id', 'e.name');
    }
    
    /**
     * Overridden to natsort() the columns.
     * 
     * @return array
     **/
    public function findPairsForSelectForm()
    {
        $pairs = parent::findPairsForSelectForm();
        natsort($pairs);
        return $pairs;
    }
    
    /**
     * Retrieve all elements for a set (containing text only for the item)
     * 
     * @see items/form.php
     * @see display_form_input_for_element()
     * @param Item
     * @param string The name of the set it belongs to.
     * @return Element
     **/
    public function findForItemBySet($item, $elementSet)
    {
        // Select all the elements for a given set
        $select = $this->getSelect();
        $db = $this->getDb();
        
        // Join on the element_sets table
        $select->joinInner(array('es'=>$db->ElementSet), 'es.id = e.element_set_id', array());
        $select->where('es.name = ?', (string) $elementSet);
        
        $elements = $this->fetchObjects($select);
       
       // Populate those element records with the values for a given item
       return $this->assignTextToElements($elements, $item->ItemsElements);
    }
    
    /**
     * Assign a set of Element texts to a set of Elements.
     *
     * @internal I'm not sure this belongs in the ElementTable class, because its
     * not a finder method, but currently the code is split across multiple places
     * and this is an attempt to consolidate it.
     * @param array Set of Element records.
     * @param array Set of ItemsElements records.
     * @return array Set of elements with text assigned to it.
     **/
    public function assignTextToElements($elements, $itemsElements)
    {
        // Sort the ItemsElements text values into their correct Element records                    
        // Speed could be improved on this.  
        foreach ($elements as $key => $element) {
            foreach ($itemsElements as $iKey => $itemElement) {
                if ($itemElement->element_id == $element->id) {
                    $element->addText($itemElement);
                }
            }
        }
        
        return $elements;
    }
    
    /**
     * Retrieve a set of Element records that belong to a specific Item Type.
     * 
     * @see Item::getItemTypeElements()
     * @param integer
     * @return array Set of element records.
     **/
    public function findByItemType($itemTypeId)
    {
        $select = $this->getSelect();
        $db = $this->getDb();
        $select->joinInner(array('ite'=>$db->ItemTypesElements), 'ite.element_id = e.id', array());
        $select->where('ite.item_type_id = ?');
        
        $elements = $this->fetchObjects($select, array($itemTypeId)); 

       return $elements;
    }
}
