<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for step.
 *
 * @package    local_usertours
 * @copyright  2016 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Tests for step.
 *
 * @package    local_usertours
 * @copyright  2016 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group local_usertours
 */
class step_testcase extends advanced_testcase {

    /**
     * @var moodle_database
     */
    protected $DB;

    /**
     * Setup to store the DB reference.
     */
    public function setUp() {
        global $DB;

        $this->DB = $DB;
    }

    /**
     * Tear down to restore the original DB reference.
     */
    public function tearDown() {
        global $DB;

        $DB = $this->DB;
    }

    /**
     * Helper to mock the database.
     *
     * @return moodle_database
     */
    public function mock_database() {
        global $DB;

        $DB = $this->getMockBuilder('moodle_database')
            ->getMock()
            ;

        return $DB;
    }

    /**
     * Data provider for the dirty value tester.
     *
     * @return array
     */
    public function dirty_value_provider() {
        return [
                'tourid' => [
                        'tourid',
                        [1],
                    ],
                'title' => [
                        'title',
                        ['Lorem'],
                    ],
                'content' => [
                        'content',
                        ['Lorem'],
                    ],
                'targettype' => [
                        'targettype',
                        ['Lorem'],
                    ],
                'targetvalue' => [
                        'targetvalue',
                        ['Lorem'],
                    ],
                'sortorder' => [
                        'sortorder',
                        [1],
                    ],
                'config' => [
                        'config',
                        ['key', 'value'],
                    ],
            ];
    }

    /**
     * Test the fetch function.
     */
    public function test_fetch() {
        $step = $this->getMockBuilder('\local_usertours\step')
            ->setMethods(['reload_from_record'])
            ->getMock()
            ;

        $idretval = rand(1, 100);
        $DB = $this->mock_database();
        $DB->method('get_record')
            ->willReturn($idretval)
            ;

        $retval = rand(1, 100);
        $step->expects($this->once())
            ->method('reload_from_record')
            ->with($this->equalTo($idretval))
            ->wilLReturn($retval)
            ;

        $rc = new \ReflectionClass('\local_usertours\step');
        $rcm = $rc->getMethod('fetch');
        $rcm->setAccessible(true);

        $id = rand(1, 100);
        $this->assertEquals($retval, $rcm->invoke($step, 'fetch', $id));
    }

    /**
     * Test that setters mark things as dirty.
     *
     * @dataProvider dirty_value_provider
     */
    public function test_dirty_values($name, $value) {
        $step = new \local_usertours\step();
        $method = 'set_' . $name;
        call_user_func_array([$step, $method], $value);

        $rc = new \ReflectionClass('local_usertours\step');
        $rcp = $rc->getProperty('dirty');
        $rcp->setAccessible(true);

        $this->assertTrue($rcp->getValue($step));
    }

    /**
     * Provider for is_first_step.
     *
     * @return array
     */
    public function step_sortorder_provider() {
        return [
                [0, 5, true, false],
                [1, 5, false, false],
                [4, 5, false, true],
            ];
    }

    /**
     * Test is_first_step.
     *
     * @dataProvider step_sortorder_provider
     * @param   int     $sortorder      The sortorder to check
     * @param   int     $count          Unused in this function
     * @param   bool    $isfirst        Whether this is the first step
     * @param   bool    $islast         Whether this is the last step
     */
    public function test_is_first_step($sortorder, $count, $isfirst, $islast) {
        $step = $this->getMockBuilder('\local_usertours\step')
            ->setMethods(['get_sortorder'])
            ->getMock();

        $step->expects($this->once())
            ->method('get_sortorder')
            ->willReturn($sortorder)
            ;

        $this->assertEquals($isfirst, $step->is_first_step());
    }

    /**
     * Test is_last_step.
     *
     * @dataProvider step_sortorder_provider
     * @param   int     $sortorder      The sortorder to check
     * @param   int     $count          Total number of steps for this test
     * @param   bool    $isfirst        Whether this is the first step
     * @param   bool    $islast         Whether this is the last step
     */
    public function test_is_last_step($sortorder, $count, $isfirst, $islast) {
        $step = $this->getMockBuilder('\local_usertours\step')
            ->setMethods(['get_sortorder', 'get_tour'])
            ->getMock();

        $tour = $this->getMockBuilder('\local_usertours\tour')
            ->setMethods(['count_steps'])
            ->getMock();

        $step->expects($this->once())
            ->method('get_tour')
            ->willReturn($tour)
            ;

        $tour->expects($this->once())
            ->method('count_steps')
            ->willReturn($count)
            ;

        $step->expects($this->once())
            ->method('get_sortorder')
            ->willReturn($sortorder)
            ;

        $this->assertEquals($islast, $step->is_last_step());
    }

    /**
     * Test get_config with no keys provided.
     */
    public function test_get_config_no_keys() {
        $step = new \local_usertours\step();

        $rc = new \ReflectionClass('\local_usertours\step');
        $rcp = $rc->getProperty('config');
        $rcp->setAccessible(true);

        $allvalues = (object) [
                'some' => 'value',
                'another' => 42,
                'key' => [
                    'somethingelse',
                ],
            ];

        $rcp->setValue($step, $allvalues);

        $this->assertEquals($allvalues, $step->get_config());
    }

    /**
     * Data provider for get_config.
     *
     * @return array
     */
    public function get_config_provider() {
        $allvalues = (object) [
                'some' => 'value',
                'another' => 42,
                'key' => [
                    'somethingelse',
                ],
            ];

        $tourconfig = rand(1, 100);
        $forcedconfig = rand(1, 100);

        return [
                'No initial config' => [
                        null,
                        null,
                        null,
                        $tourconfig,
                        false,
                        $forcedconfig,
                        (object) [],
                    ],
                'All values' => [
                        $allvalues,
                        null,
                        null,
                        $tourconfig,
                        false,
                        $forcedconfig,
                        $allvalues,
                    ],
                'Valid string value' => [
                        $allvalues,
                        'some',
                        null,
                        $tourconfig,
                        false,
                        $forcedconfig,
                        'value',
                    ],
                'Valid array value' => [
                        $allvalues,
                        'key',
                        null,
                        $tourconfig,
                        false,
                        $forcedconfig,
                        ['somethingelse'],
                    ],
                'Invalid value' => [
                        $allvalues,
                        'notavalue',
                        null,
                        $tourconfig,
                        false,
                        $forcedconfig,
                        $tourconfig,
                    ],
                'Configuration value' => [
                        $allvalues,
                        'placement',
                        null,
                        $tourconfig,
                        false,
                        $forcedconfig,
                        $tourconfig,
                    ],
                'Invalid value with default' => [
                        $allvalues,
                        'notavalue',
                        'somedefault',
                        $tourconfig,
                        false,
                        $forcedconfig,
                        'somedefault',
                    ],
                'Value forced at target' => [
                        $allvalues,
                        'somevalue',
                        'somedefault',
                        $tourconfig,
                        true,
                        $forcedconfig,
                        $forcedconfig,
                    ],
            ];
    }

    /**
     * Test get_config with valid keys provided.
     *
     * @dataProvider get_config_provider
     * @param   object  $values     The config values
     * @param   string  $key        The key
     * @param   mixed   $default    The default value
     * @param   mixed   $expected   The expected value
     */
    public function test_get_config_valid_keys($values, $key, $default, $tourconfig, $isforced, $forcedvalue, $expected) {
        $step = $this->getMockBuilder('\local_usertours\step')
            ->setMethods(['get_target', 'get_tour'])
            ->getMock();

        $rc = new \ReflectionClass('\local_usertours\step');
        $rcp = $rc->getProperty('config');
        $rcp->setAccessible(true);
        $rcp->setValue($step, $values);

        $target = $this->getMockBuilder('\local_usertours\target\base')
            ->disableOriginalConstructor()
            ->getMock()
            ;

        $target->expects($this->any())
            ->method('is_setting_forced')
            ->willReturn($isforced)
            ;

        $target->expects($this->any())
            ->method('get_forced_setting_value')
            ->with($this->equalTo($key))
            ->willReturn($forcedvalue)
            ;

        $step->expects($this->any())
            ->method('get_target')
            ->willReturn($target)
            ;

        $tour = $this->getMockBuilder('\local_usertours\tour')
            ->getMock()
            ;

        $tour->expects($this->any())
            ->method('get_config')
            ->willReturn($tourconfig)
            ;


        $step->expects($this->any())
            ->method('get_tour')
            ->willReturn($tour)
            ;

        $this->assertEquals($expected, $step->get_config($key, $default));
    }

    /**
     * Data provider for set_config.
     */
    public function set_config_provider() {
        $allvalues = (object) [
                'some' => 'value',
                'another' => 42,
                'key' => [
                    'somethingelse',
                ],
            ];

        $randvalue = rand(1, 100);

        $provider = [];

        $newvalues = $allvalues;
        $newvalues->some = 'unset';
        $provider['Unset an existing value'] = [
                $allvalues,
                'some',
                null,
                $newvalues,
            ];

        $newvalues = $allvalues;
        $newvalues->some = $randvalue;
        $provider['Set an existing value'] = [
                $allvalues,
                'some',
                $randvalue,
                $newvalues,
            ];

        $provider['Set a new value'] = [
                $allvalues,
                'newkey',
                $randvalue,
                (object) array_merge((array) $allvalues, ['newkey' => $randvalue]),
            ];

        return $provider;
    }

    /**
     * Test that set_config works in the anticipated fashion.
     *
     * @dataProvider set_config_provider
     */
    public function test_set_config($initialvalues, $key, $newvalue, $expected) {
        $step = new \local_usertours\step();

        $rc = new \ReflectionClass('\local_usertours\step');
        $rcp = $rc->getProperty('config');
        $rcp->setAccessible(true);
        $rcp->setValue($step, $initialvalues);

        $target = $this->getMockBuilder('\local_usertours\target\base')
            ->disableOriginalConstructor()
            ->getMock()
            ;

        $target->expects($this->any())
            ->method('is_setting_forced')
            ->willReturn(false)
            ;

        $step->set_config($key, $newvalue);

        $this->assertEquals($expected, $rcp->getValue($step));
    }

    /**
     * Ensure that non-dirty tours are not persisted.
     */
    public function test_persist_non_dirty() {
        $step = $this->getMockBuilder('\local_usertours\step')
            ->setMethods([
                    'to_record',
                    'reload',
                ])
            ->getMock()
            ;

        $step->expects($this->never())
            ->method('to_record')
            ;

        $step->expects($this->never())
            ->method('reload')
            ;

        $this->assertSame($step, $step->persist());
    }

    /**
     * Ensure that new dirty steps are persisted.
     */
    public function test_persist_dirty_new() {
        // Mock the database.
        $DB = $this->mock_database();
        $DB->expects($this->once())
            ->method('insert_record')
            ->willReturn(42)
            ;

        // Mock the tour.
        $step = $this->getMockBuilder('\local_usertours\step')
            ->setMethods([
                    'to_record',
                    'calculate_sortorder',
                    'reload',
                ])
            ->getMock()
            ;

        $step->expects($this->once())
            ->method('to_record')
            ->willReturn((object)['id' => 42]);
            ;

        $step->expects($this->once())
            ->method('calculate_sortorder')
            ;

        $step->expects($this->once())
            ->method('reload')
            ;

        $rc = new \ReflectionClass('\local_usertours\step');
        $rcp = $rc->getProperty('dirty');
        $rcp->setAccessible(true);
        $rcp->setValue($step, true);

        $this->assertSame($step, $step->persist());
    }

    /**
     * Ensure that new non-dirty, forced steps are persisted.
     */
    public function test_persist_force_new() {
        global $DB;

        // Mock the database.
        $DB = $this->mock_database();
        $DB->expects($this->once())
            ->method('insert_record')
            ->willReturn(42)
            ;

        // Mock the tour.
        $step = $this->getMockBuilder('\local_usertours\step')
            ->setMethods([
                    'to_record',
                    'calculate_sortorder',
                    'reload',
                ])
            ->getMock()
            ;

        $step->expects($this->once())
            ->method('to_record')
            ->willReturn((object)['id' => 42]);
            ;

        $step->expects($this->once())
            ->method('calculate_sortorder')
            ;

        $step->expects($this->once())
            ->method('reload')
            ;

        $this->assertSame($step, $step->persist(true));
    }

    /**
     * Ensure that existing dirty steps are persisted.
     */
    public function test_persist_dirty_existing() {
        // Mock the database.
        $DB = $this->mock_database();
        $DB->expects($this->once())
            ->method('update_record')
            ;

        // Mock the tour.
        $step = $this->getMockBuilder('\local_usertours\step')
            ->setMethods([
                    'to_record',
                    'calculate_sortorder',
                    'reload',
                ])
            ->getMock()
            ;

        $step->expects($this->once())
            ->method('to_record')
            ->willReturn((object)['id' => 42]);
            ;

        $step->expects($this->never())
            ->method('calculate_sortorder')
            ;

        $step->expects($this->once())
            ->method('reload')
            ;

        $rc = new \ReflectionClass('\local_usertours\step');
        $rcp = $rc->getProperty('id');
        $rcp->setAccessible(true);
        $rcp->setValue($step, 42);

        $rcp = $rc->getProperty('dirty');
        $rcp->setAccessible(true);
        $rcp->setValue($step, true);

        $this->assertSame($step, $step->persist());
    }

    /**
     * Ensure that existing non-dirty, forced steps are persisted.
     */
    public function test_persist_force_existing() {
        global $DB;

        // Mock the database.
        $DB = $this->mock_database();
        $DB->expects($this->once())
            ->method('update_record')
            ;

        // Mock the tour.
        $step = $this->getMockBuilder('\local_usertours\step')
            ->setMethods([
                    'to_record',
                    'calculate_sortorder',
                    'reload',
                ])
            ->getMock()
            ;

        $step->expects($this->once())
            ->method('to_record')
            ->willReturn((object)['id' => 42]);
            ;

        $step->expects($this->never())
            ->method('calculate_sortorder')
            ;

        $step->expects($this->once())
            ->method('reload')
            ;

        $rc = new \ReflectionClass('\local_usertours\step');
        $rcp = $rc->getProperty('id');
        $rcp->setAccessible(true);
        $rcp->setValue($step, 42);

        $this->assertSame($step, $step->persist(true));
    }

    /**
     * Check that a tour which has never been persisted is removed correctly.
     */
    public function test_remove_non_persisted() {
        $step = $this->getMockBuilder('\local_usertours\step')
            ->setMethods(null)
            ->getMock()
            ;

        // Mock the database.
        $DB = $this->mock_database();
        $DB->expects($this->never())
            ->method('delete_records')
            ;

        $this->assertNull($step->remove());
    }

    /**
     * Check that a tour which has been persisted is removed correctly.
     */
    public function test_remove_persisted() {
        $id = rand(1, 100);

        $tour = $this->getMockBuilder('\local_usertours\tour')
            ->setMethods([
                    'reset_step_sortorder',
                ])
            ->getMock()
            ;

        $tour->expects($this->once())
            ->method('reset_step_sortorder')
            ;

        $step = $this->getMockBuilder('\local_usertours\step')
            ->setMethods([
                    'get_tour',
                ])
            ->getMock()
            ;

        $step->expects($this->once())
            ->method('get_tour')
            ->willReturn($tour)
            ;

        // Mock the database.
        $DB = $this->mock_database();
        $DB->expects($this->once())
            ->method('delete_records')
            ->with($this->equalTo('usertours_steps'), $this->equalTo(['id' => $id]))
            ;

        $rc = new \ReflectionClass('\local_usertours\step');
        $rcp = $rc->getProperty('id');
        $rcp->setAccessible(true);
        $rcp->setValue($step, $id);

        $this->assertEquals($id, $step->get_id());
        $this->assertNull($step->remove());
    }

    /**
     * Data provider for the get_ tests.
     *
     * @return array
     */
    public function getter_provider() {
        return [
                'id' => [
                        'id',
                        rand(1, 100),
                    ],
                'tourid' => [
                        'tourid',
                        rand(1, 100),
                    ],
                'title' => [
                        'title',
                        'Lorem',
                    ],
                'content' => [
                        'content',
                        'Lorem',
                    ],
                'targettype' => [
                        'targettype',
                        'Lorem',
                    ],
                'targetvalue' => [
                        'targetvalue',
                        'Lorem',
                    ],
                'sortorder' => [
                        'sortorder',
                        rand(1, 100),
                    ],
            ];
    }

    /**
     * Test that getters return the configured value.
     *
     * @dataProvider getter_provider
     */
    public function test_getters($key, $value) {
        $step = new \local_usertours\step();

        $rc = new \ReflectionClass('\local_usertours\step');

        $rcp = $rc->getProperty($key);
        $rcp->setAccessible(true);
        $rcp->setValue($step, $value);

        $getter = 'get_' . $key;

        $this->assertEquals($value, $step->$getter());
    }

    /**
     * Override the getMock method to allow abstract methods to be included in the mock class.
     * Returns a mock object for the specified class.
     *
     * @param  string     $originalClassName       Name of the class to mock.
     * @param  array|null $methods                 When provided, only methods whose names are in the array
     *                                             are replaced with a configurable test double. The behavior
     *                                             of the other methods is not changed.
     *                                             Providing null means that no methods will be replaced.
     * @param  array      $arguments               Parameters to pass to the original class' constructor.
     * @param  string     $mockClassName           Class name for the generated test double class.
     * @param  boolean    $callOriginalConstructor Can be used to disable the call to the original class' constructor.
     * @param  boolean    $callOriginalClone       Can be used to disable the call to the original class' clone constructor.
     * @param  boolean    $callAutoload            Can be used to disable __autoload() during the generation of the test double class.
     * @param  boolean    $cloneArguments
     * @return PHPUnit_Framework_MockObject_MockObject
     * @throws PHPUnit_Framework_Exception
     * @since  Method available since Release 3.0.0
     */
    public function getMock($originalClassName, $methods = array(), array $arguments = array(), $mockClassName = '',
        $callOriginalConstructor = TRUE, $callOriginalClone = TRUE, $callAutoload = TRUE) {
        if ($methods !== null) {
            $methods = array_unique(array_merge($methods,
                self::getAbstractMethods($originalClassName, $callAutoload)));
        }
        return parent::getMock($originalClassName, $methods, $arguments, $mockClassName, $callOriginalConstructor, $callOriginalClone, $callAutoload);
    }

    /**
     * Returns an array containing the names of the abstract methods in <code>$class</code>.
     *
     * @param string $class name of the class
     * @return array zero or more abstract methods names
     */
    public static function getAbstractMethods($class, $autoload=true) {
        $methods = array();
        if (class_exists($class, $autoload) || interface_exists($class, $autoload)) {
            $reflector = new ReflectionClass($class);
            foreach ($reflector->getMethods() as $method) {
                if ($method->isAbstract()) {
                    $methods[] = $method->getName();
                }
            }
        }
        return $methods;
    }
}
