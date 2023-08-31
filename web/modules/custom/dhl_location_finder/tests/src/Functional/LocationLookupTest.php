<?php

namespace Drupal\Tests\dhl_location_finder\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the Location finder form fetches results..
 *
 * @group dhl_location_finder
 */
class LocationLookupTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['dhl_location_finder'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'olivero';

  /**
   * Tests that the Lorem ipsum page can be reached.
   */
  public function testLocationFinderPageExists() {
    // Generator test:
    $this->drupalLogin($this->drupalCreateUser(['access content']));
    $this->drupalGet('/dhl-location-finder');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the custom module form submission.
   */
  public function testCustomModuleFormSubmission() {
    // Ensure that the user has the necessary permission.
    $this->drupalLogin($this->drupalCreateUser(['access content']));

    // Navigate to the form page.
    $this->drupalGet('/dhl-location-finder');

    // Fill in the form values.
    $edit = [
      'country_code' => 'DE',
      'city' => 'Bonn',
      'postal_code' => '53313',
    ];
    $this->submitForm($edit, 'Find Locations');

    // Check for the success message.
    $this->assertSession()->pageTextNotContains('Please try again later!');;
    $this->assertSession()->pageTextNotContains('Please try with another city name and postal code for the selected country code!');;
  }

}
