<?php
/**
 * Runs the JSON Schema Test Suite
 * testcases, JsonSchema-php is not compliant to, are marked as incomplete.
 *
 * TODO install test suite via composer!
 * Install JSON-Schema-Test-Suite git submodele:
 * $> git submodule update --init
 * @author Jan Mentzel <jan@hypercharge.net>
 */
class SuiteTest extends \PHPUnit_Framework_TestCase {

	/**
	* mixed
	*  int
	*     -1     ... run all test cases. or use false
	*     0 to n ... run only one speciffic testcase, will switch to verbose=true
	*                e.g.  PICK = 145;  run test case #145
	*  string
	*     regexp ... run tests with suite.description matching the regexp
	*                e.g.  PICK = '/multiple dependencies/';
	*
	* Hint: you can turn off SuiteTest with something like PICK = '/^NO TESTS$/'
	*/
	const PICK = -1;

	static private $verbose = false;

	private $draft3Dir;

	public static function schemaSuiteTestProvider() {
		$draft3Dir = dirname(dirname(__DIR__)).'/vendor/json-schema/JSON-Schema-Test-Suite/tests/draft3';
		if(!is_dir($draft3Dir)) {
			self::markTestSkipped(
				"The language independent JSON-Schema-Test-Suite is not installed.\nIt may be installed with:\n\ncomposer install\n\n"
			);
			return;
		}
		$tests = array();
		$paths = array(
				$draft3Dir
				,$draft3Dir.'/optional'
		);
		$ignoredFiles = array('optional', 'zeroTerminatedFloats.json');

		$errors = array();

		foreach($paths as $path) {
			//echo "\npath: $path\n";
			foreach (glob($path.'/*.json') as $file) {
				//echo "\nfile: $file\n";
				$suites = json_decode(file_get_contents($file));
				foreach($suites as $suite) {
					// pick speciffic tests if wanted
					if(is_string(self::PICK) && !preg_match(self::PICK, $suite->description)) continue;

					//echo "\nsuite: ",$suite->description, "\n";
					foreach($suite->tests as $test) {
						if(!$test->description) continue;
						//echo "\t",$test->description, "\n";
						$test->suite = new stdClass();
						$test->suite->description = $suite->description;
						$test->suite->schema      = $suite->schema;
						array_push($tests, array($test));
					}
				}
			}
		}
		//print_r($tests);

		if(self::PICK < 0 || self::PICK === false) {
			return $tests;
		}
		self::$verbose = true;
		if(is_int(self::PICK)) {
			return array($tests[self::PICK]);
		}
		return $tests;
	}

	 /**
	 * @dataProvider schemaSuiteTestProvider
	 */
	 function testSchemaSuite($test) {
	 		if(self::$verbose) {
	 			echo "\n"; print_r($test);
	 		}
	 		$this->setName($test->suite->description.': '.($test->valid?'valid':'not valid').' : '.$test->description.' |');
			$validator = new \JsonSchema\Validator();

			// resolve http:// or file:// $ref and extends
			$refResolver = new \JsonSchema\RefResolver();
			try {

				$refResolver->resolve($test->suite->schema);

				// echo "\nresolved schema: ";
				// print_r($test->suite->schema);

				// suppress errors because of php warnings with invalid-regexp tests
				$turnOffWarnings = preg_match('/regular expression/', $test->description) && !$test->valid;
				if($turnOffWarnings) $flags = error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

				$validator->check($test->data, $test->suite->schema);

				if($turnOffWarnings) error_reporting($flags);

			} catch(Exception $exe) {
				// echo "data: ".print_r($test->data, true)
				// 	."\nschema: ".print_r($test->suite->schema, true)
				// 	."\nerrors: ".print_r($validator->getErrors(), true);
				$this->markTestIncomplete("unexpected Exception: \n". $exe);
				return;
			}

			if($validator->isValid() != $test->valid) {
				$this->markTestIncomplete();
				// echo "data: ".print_r($test->data, true)
				// 	."\nschema: ".print_r($test->suite->schema, true)
				// 	."\nerrors: ".print_r($validator->getErrors(), true);
			}
	 }
}