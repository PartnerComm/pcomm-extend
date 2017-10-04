<?php

namespace PComm\extend;

function add_action($action, $data)
{
    //Do nothing, just mocking
}

function _x($input1, $input2)
{
    //Do nothing
}

function __($input1)
{
    //Do nothing
}

class TaxonomyTest extends \PHPUnit_Framework_TestCase
{
    public function testIsSetup()
    {
        $tax = 'tax-string';
        $options = ['option1', 'option2'];
        $labels = ['label1', 'label2'];
        $supports = ['support1', 'support2'];

        $combined_labels = $labels + [
                'singular_name' => ucwords($tax),
                'plural_name' => ucwords($tax)
            ];

        $taxonomy = new \Pcomm\extend\Taxonomy($tax, $options, $labels, $supports);

        $this->assertEquals($tax, $taxonomy->tax);
        $this->assertEquals($supports, $taxonomy->supports);
        $this->assertEquals($combined_labels, $taxonomy->labels);

    }
}