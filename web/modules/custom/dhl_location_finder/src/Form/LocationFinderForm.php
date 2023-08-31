<?php

namespace Drupal\dhl_location_finder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LocationFinderForm extends FormBase
{

  public function getFormId()
  {
    return 'dhl_location_finder_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['country_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Country'),
      '#required' => TRUE,
      '#autocomplete_route_name' => 'dhl_location_finder.autocomplete',
      '#attributes' => [
        'autocomplete' => "new-password",
        'readonly' => 'readonly',
        'onfocus' => "this.removeAttribute('readonly');"
      ]
    ];

    $form['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#required' => TRUE,
      '#attributes' => [
        'autocomplete' => "new-password",
      ]
    ];
    $form['postal_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postal Code'),
      '#required' => TRUE,
      '#attributes' => [
        'autocomplete' => "new-password",
      ]
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Find Locations'),
    ];
    $form['#attributes']['autocomplete'] = "off";

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $city = $form_state->getValue('city');
    $postalCode = $form_state->getValue('postal_code');
    $countryCode = $form_state->getValue('country_code');

    /*kint($form_state->getUserInput());
    exit;*/

    // Prepare the API request.
    $client = new Client();
    $response = $client->get('https://api-sandbox.dhl.com/location-finder/v1/find-by-address', [
      'headers' => [
        'DHL-API-Key' => 'demo-key',
      ],
      'query' => [
        'countryCode' => $countryCode,
        'addressLocality' => $city,
        'postalCode' => $postalCode,
      ],
    ]);

    $locations = json_decode($response->getBody(), TRUE);
    //kint($locations); exit;

    // Process and filter locations.
    $filteredLocations = [];
    foreach ($locations['locations'] as $location) {
      if ($this->isValidLocation($location)) {
        $filteredLocations[] = $this->formatLocation($location);
      }
    }

    // Output filtered locations in YAML format.
    $yamlOutput = Yaml::encode($filteredLocations);

    // Display the YAML output.
    \Drupal::messenger()->addMessage($yamlOutput);
  }

  private function isValidLocation(array $location)
  {
    // Check if the location works on weekends and has an even number in the address.
    // Implement your validation logic here.
    return true;
  }

  private function formatLocation(array $location)
  {
    // Format the location data as required in the YAML output.
    // Implement your formatting logic here.
    return $location;
  }
}
