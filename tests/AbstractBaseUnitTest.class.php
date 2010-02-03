<?php
abstract class referencing_tests_AbstractBaseUnitTest extends referencing_tests_AbstractBaseTest
{
	/**
	 * @return void
	 */
	public function prepareTestCase()
	{
		$this->resetDatabase();
	}
}