<?php
namespace Cobweb\DataFilter\Tests\Unit;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Tesseract\Datafilter\Component\DataFilter;
use Tesseract\Tesseract\Frontend\PluginControllerBase;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for parsing filter configurations
 *
 * @author Francois Suter <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_datafilter
 */
class ConfigurationParsingTest extends UnitTestCase
{
    /**
     * @var array List of globals to exclude (contain closures which cannot be serialized)
     */
    protected $backupGlobalsBlacklist = array('TYPO3_LOADED_EXT', 'TYPO3_CONF_VARS');

    /**
     * Forces some GET variables for testing.
     *
     * @return void
     */
    public function setUp()
    {
        $_GET['tx_choice'] = array('foo', 'bar');
    }

    /**
     * Provides configurations and the expected result for testing filters.
     *
     * @return array
     */
    public function configurationProvider()
    {
        $configurations = array(
                'equality on main (with alternative values, multiline inc. comments, filter key)' => array(
                        'definition' => array(
                                'configuration' => "main.tt_content.uid   = gp:unknown // 42\n#tt_content.uid > 10\nhead :: tt_content.header start foo",
                                'logical_operator' => 'AND'
                        ),
                        'result' => array(
                                'filters' => array(
                                        0 => array(
                                                'table' => 'tt_content',
                                                'field' => 'uid',
                                                'main' => true,
                                                'void' => false,
                                                'conditions' => array(
                                                        0 => array(
                                                                'operator' => '=',
                                                                'value' => '42',
                                                                'negate' => false
                                                        )
                                                ),
                                                'string' => 'main.tt_content.uid   = gp:unknown // 42'
                                        ),
                                        'head' => array(
                                                'table' => 'tt_content',
                                                'field' => 'header',
                                                'main' => false,
                                                'void' => false,
                                                'conditions' => array(
                                                        0 => array(
                                                                'operator' => 'start',
                                                                'value' => 'foo',
                                                                'negate' => false
                                                        )
                                                ),
                                                'string' => 'head :: tt_content.header start foo'
                                        )
                                ),
                                'logicalOperator' => 'AND',
                                'limit' => array(
                                        'max' => 0,
                                        'offset' => 0,
                                        'pointer' => 0
                                ),
                                'orderby' => array(),
                                'parsed' => array(
                                        'filters' => array(
                                                'tt_content.uid' => array(
                                                        0 => array(
                                                                'condition' => '= 42',
                                                                'operator' => '=',
                                                                'value' => '42',
                                                                'negate' => false
                                                        )
                                                ),
                                                'tt_content.header' => array(
                                                        'head' => array(
                                                                'condition' => 'start foo',
                                                                'operator' => 'start',
                                                                'value' => 'foo',
                                                                'negate' => false
                                                        )
                                                )
                                        )
                                )
                        ),
                ),
                'in interval' => array(
                        'definition' => array(
                                'configuration' => 'tt_content.uid = [100,200]',
                                'logical_operator' => 'AND'
                        ),
                        'result' => array(
                                'filters' => array(
                                        0 => array(
                                                'table' => 'tt_content',
                                                'field' => 'uid',
                                                'main' => false,
                                                'void' => false,
                                                'conditions' => array(
                                                        0 => array(
                                                                'operator' => '>=',
                                                                'value' => '100',
                                                                'negate' => false
                                                        ),
                                                        1 => array(
                                                                'operator' => '<=',
                                                                'value' => '200',
                                                                'negate' => false
                                                        )
                                                ),
                                                'string' => 'tt_content.uid = [100,200]'
                                        )
                                ),
                                'logicalOperator' => 'AND',
                                'limit' => array(
                                        'max' => 0,
                                        'offset' => 0,
                                        'pointer' => 0
                                ),
                                'orderby' => array(),
                                'parsed' => array(
                                        'filters' => array(
                                                'tt_content.uid' => array(
                                                        0 => array(
                                                                'condition' => '= [100,200]',
                                                                'operator' => '=',
                                                                'value' => '[100,200]',
                                                                'negate' => false
                                                        )
                                                )
                                        )
                                )
                        ),
                ),
                'not null' => array(
                        'definition' => array(
                                'configuration' => 'tt_content.image != \NULL',
                                'logical_operator' => 'AND'
                        ),
                        'result' => array(
                                'filters' => array(
                                        0 => array(
                                                'table' => 'tt_content',
                                                'field' => 'image',
                                                'main' => false,
                                                'void' => false,
                                                'conditions' => array(
                                                        0 => array(
                                                                'operator' => '=',
                                                                'value' => '\null',
                                                                'negate' => true
                                                        )
                                                ),
                                                'string' => 'tt_content.image != \NULL'
                                        )
                                ),
                                'logicalOperator' => 'AND',
                                'limit' => array(
                                        'max' => 0,
                                        'offset' => 0,
                                        'pointer' => 0
                                ),
                                'orderby' => array(),
                                'parsed' => array(
                                        'filters' => array(
                                                'tt_content.image' => array(
                                                        0 => array(
                                                                'condition' => '!= \NULL',
                                                                'operator' => '=',
                                                                'value' => '\NULL',
                                                                'negate' => true
                                                        )
                                                )
                                        )
                                )
                        ),
                ),
                'array of gp values' => array(
                        'definition' => array(
                                'configuration' => 'tt_content.header like gp:tx_choice',
                                'logical_operator' => 'AND'
                        ),
                        'result' => array(
                                'filters' => array(
                                        0 => array(
                                                'table' => 'tt_content',
                                                'field' => 'header',
                                                'main' => false,
                                                'void' => false,
                                                'conditions' => array(
                                                        0 => array(
                                                                'operator' => 'like',
                                                                'value' => array(
                                                                        'foo',
                                                                        'bar'
                                                                ),
                                                                'negate' => false
                                                        )
                                                ),
                                                'string' => 'tt_content.header like gp:tx_choice'
                                        )
                                ),
                                'logicalOperator' => 'AND',
                                'limit' => array(
                                        'max' => 0,
                                        'offset' => 0,
                                        'pointer' => 0
                                ),
                                'orderby' => array(),
                                'parsed' => array(
                                        'filters' => array(
                                                'tt_content.header' => array(
                                                        0 => array(
                                                                'condition' => 'like foo,bar',
                                                                'operator' => 'like',
                                                                'value' => 'foo,bar',
                                                                'negate' => false
                                                        )
                                                )
                                        )
                                )
                        )
                ),
                'date with void and OR' => array(
                        'definition' => array(
                                'configuration' => 'void.tt_content.tstamp > strtotime:2010-01-01',
                                'logical_operator' => 'OR'
                        ),
                        'result' => array(
                                'filters' => array(
                                        0 => array(
                                                'table' => 'tt_content',
                                                'field' => 'tstamp',
                                                'main' => false,
                                                'void' => true,
                                                'conditions' => array(
                                                        0 => array(
                                                                'operator' => '>',
                                                                'value' => '1262300400',
                                                                'negate' => false
                                                        )
                                                ),
                                                'string' => 'void.tt_content.tstamp > strtotime:2010-01-01'
                                        )
                                ),
                                'logicalOperator' => 'OR',
                                'limit' => array(
                                        'max' => 0,
                                        'offset' => 0,
                                        'pointer' => 0
                                ),
                                'orderby' => array(),
                                'parsed' => array(
                                        'filters' => array(
                                                'tt_content.tstamp' => array(
                                                        0 => array(
                                                                'condition' => '> 1262300400',
                                                                'operator' => '>',
                                                                'value' => '1262300400',
                                                                'negate' => false
                                                        )
                                                )
                                        )
                                )
                        ),
                ),
                'limits and order' => array(
                        'definition' => array(
                                'configuration' => '',
                                'logical_operator' => 'AND',
                                'orderby' => "field = tt_content.tstamp\norder = desc\nfield = tt_content.starttime\norder=ASC\nengine=source",
                                'limit_start' => 'gp:page // 0',
                                'limit_offset' => 'gp:max // 20',
                                'limit_pointer' => ''
                        ),
                        'result' => array(
                                'filters' => array(),
                                'logicalOperator' => 'AND',
                                'limit' => array(
                                        'max' => 0,
                                        'offset' => 20,
                                        'pointer' => 0
                                ),
                                'orderby' => array(
                                        0 => array(
                                                'table' => 'tt_content',
                                                'field' => 'tstamp',
                                                'order' => 'desc',
                                                'engine' => ''
                                        ),
                                        2 => array(
                                                'table' => 'tt_content',
                                                'field' => 'starttime',
                                                'order' => 'ASC',
                                                'engine' => 'source'
                                        )
                                ),
                                'parsed' => array(
                                        'filters' => array()
                                )
                        ),
                ),
                // Ordering configuration with errors or some weirdness:
                // - first line is skipped because we don't have a "field" yet
                // - empty line after field is removed entirely
                // - so is line with comment
                // - second ordering for first field overrides first ordering
                // - engine value for the second field is invalid
                'ordering (unusual or bad configuration)' => array(
                        'definition' => array(
                                'configuration' => '',
                                'logical_operator' => 'AND',
                                'orderby' => "order = foo\nfield = tt_content.tstamp\n\n# Comment\norder = desc\norder = asc\nengine = source\nfield = tt_content.starttime\norder=foo\nengine = bar",
                                'limit_start' => '',
                                'limit_offset' => '',
                                'limit_pointer' => ''
                        ),
                        'result' => array(
                                'filters' => array(),
                                'logicalOperator' => 'AND',
                                'limit' => array(
                                        'max' => 0,
                                        'offset' => 0,
                                        'pointer' => 0
                                ),
                                'orderby' => array(
                                        1 => array(
                                                'table' => 'tt_content',
                                                'field' => 'tstamp',
                                                'order' => 'asc',
                                                'engine' => 'source'
                                        ),
                                        5 => array(
                                                'table' => 'tt_content',
                                                'field' => 'starttime',
                                                'order' => 'foo',
                                                'engine' => ''
                                        )
                                ),
                                'parsed' => array(
                                        'filters' => array()
                                )
                        ),
                ),
                'random ordering (clean definition)' => array(
                        'definition' => array(
                                'configuration' => '',
                                'logical_operator' => 'AND',
                                'orderby' => '\rand',
                        ),
                        'result' => array(
                                'filters' => array(),
                                'logicalOperator' => 'AND',
                                'limit' => array(
                                        'max' => 0,
                                        'offset' => 0,
                                        'pointer' => 0
                                ),
                                'orderby' => array(
                                        1 => array(
                                                'table' => '',
                                                'field' => '',
                                                'order' => 'RAND',
                                                'engine' => ''
                                        )
                                ),
                                'parsed' => array(
                                        'filters' => array()
                                )
                        ),
                ),
                'random ordering (not clean, but still valid definition)' => array(
                        'definition' => array(
                                'configuration' => '',
                                'logical_operator' => 'AND',
                                'orderby' => 'field = \rand',
                        ),
                        'result' => array(
                                'filters' => array(),
                                'logicalOperator' => 'AND',
                                'limit' => array(
                                        'max' => 0,
                                        'offset' => 0,
                                        'pointer' => 0
                                ),
                                'orderby' => array(
                                        1 => array(
                                                'table' => '',
                                                'field' => '',
                                                'order' => 'RAND',
                                                'engine' => ''
                                        )
                                ),
                                'parsed' => array(
                                        'filters' => array()
                                )
                        ),
                )
        );
        return $configurations;
    }

    /**
     * Tests the parsing of various filters.
     *
     * @param array $definition The raw filter definition
     * @param array $result The expected structure of the parsed filter
     * @test
     * @dataProvider configurationProvider
     */
    public function getFilterStructureParsesFilterConfiguration($definition, $result)
    {
        /** @var DataFilter $filterObject */
        $filterObject = GeneralUtility::makeInstance(DataFilter::class);
        /** @var $controller PluginControllerBase */
        $controller = $this->getMock(PluginControllerBase::class);
        $filterObject->setController($controller);
        $filterObject->setData($definition);
        $actualResult = $filterObject->getFilterStructure();
        // Check if the "structure" part if correct
        self::assertEquals($result, $actualResult);
    }
}
