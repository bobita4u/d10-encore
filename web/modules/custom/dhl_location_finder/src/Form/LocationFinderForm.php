<?php

namespace Drupal\dhl_location_finder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class LocationFinderForm extends FormBase {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructor for MymoduleServiceExample.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * @return string
   */
  public function getFormId() {
    return 'dhl_location_finder_form';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['country_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Country'),
      '#required' => TRUE,
      '#autocomplete_route_name' => 'dhl_location_finder.autocomplete',
      '#attributes' => [
        'autocomplete' => "new-password",
        'readonly' => 'readonly',
        'onfocus' => "this.removeAttribute('readonly');",
      ],
      '#description' => $this->t('E.g., IN for India.'),
    ];

    $form['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#required' => TRUE,
      '#attributes' => [
        'autocomplete' => "new-password",
      ],
      '#description' => $this->t('E.g., Delhi.'),
    ];
    $form['postal_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postal Code'),
      '#required' => TRUE,
      '#attributes' => [
        'autocomplete' => "new-password",
      ],
      '#description' => $this->t('E.g., 110001'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Find Locations'),
    ];
    $form['#attributes']['autocomplete'] = "off";

    return $form;
  }

  /**
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen($form_state->getValue('country_code')) != 2) {
      $form_state->setErrorByName('country_code', $this->t('Please enter a valid Country code!'));
    }
    if (strlen($form_state->getValue('postal_code')) < 5 || strlen($form_state->getValue('postal_code')) > 9) {
      $form_state->setErrorByName('postal_code', $this->t('Please enter a valid Postal Code!'));
    }
    $re = '/^[a-zA-Z\s]+$/';
    if (!preg_match($re, $form_state->getValue('city'))) {
      $form_state->setErrorByName('city', $this->t('Please enter a valid City name!'));
    }
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return void
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $city = $form_state->getValue('city');
    $postalCode = $form_state->getValue('postal_code');
    $countryCode = $form_state->getValue('country_code');

    // Prepare the API request.
    //$client = new Client();
    $response = $this->httpClient->get(
      'https://api-sandbox.dhl.com/location-finder/v1/find-by-address', [
        'headers' => [
          'DHL-API-Key' => 'demo-key',
        ],
        'query' => [
          'countryCode' => $countryCode,
          'addressLocality' => $city,
          'postalCode' => $postalCode,
        ],
      ]
    );

    if ($response->getStatusCode() == 200) {
      $locations = json_decode($response->getBody(), TRUE);

      // Process and filter locations.
      $filteredLocations = [];
      foreach ($locations['locations'] as $location) {
        if ($this->isValidLocation($location)) {
          $filteredLocations[] = $location;
        }
      }

      if (!empty($filteredLocations)) {
        // Output filtered locations in YAML format.
        $yamlOutput = $this->formatLocation($filteredLocations);

        // Display the YAML output.
        \Drupal::messenger()->addMessage($yamlOutput);
      }
      else {
        \Drupal::messenger()
          ->addMessage($this->t("Please try with another city name and postal code for the selected country code!"));
      }
    }
    else {
      \Drupal::messenger()->addMessage($this->t("Please try again later!"));
    }
  }

  /**
   * @param array $location
   *
   * @return true
   */
  private function isValidLocation(array $location) {
    // Check if the location works on weekends and has an even number in the address.
    // Check for odd number in address & skip.
    $streetAddress = $location['place']['address']['streetAddress'];
    $isValid = 1;

    preg_match_all('/[0-9]+/', $streetAddress, $matches);

    if (!empty($matches)) {
      foreach ($matches as $match) {
        $isValid = (!empty(array_filter($match, [$this, 'isOdd']))) ? 0 : 1;
      }
    }

    // Check if not working on weekends then skip.
    $openingHours = $location['openingHours'];
    $daysOfWeek = [];
    foreach ($openingHours as $openingHour) {
      $uriSegments = explode("/", parse_url($openingHour['dayOfWeek'], PHP_URL_PATH));
      $daysOfWeek[] = array_pop($uriSegments);
    }

    $reqDays = ["Saturday", "Sunday"];
    $isValid = (!($this->inArrayAny($reqDays, $daysOfWeek))) ? 0 : 1;

    return $isValid;
  }

  /**
   *
   */
  private function isOdd($num) {
    // Returns whether the input integer is odd.
    return $num & 1;
  }

  /**
   * @param array $location
   *
   * @return string
   */
  private function formatLocation(array $location) {
    // Format the location data as required in the YAML output.
    $yamlOutput = Yaml::encode($location);

    return $yamlOutput;
  }

  /**
   *
   */
  private function checkWorkingWeekends($location) {
    $bool = 1;

    return $bool;
  }

  /**
   * ALL needles exist.
   *
   * @param $needles
   * @param $haystack
   *
   * @return bool
   */
  private function inArrayAll($needles, $haystack) {
    return empty(array_diff($needles, $haystack));
  }

  /**
   * ANY of the needles exist.
   *
   * @param $needles
   * @param $haystack
   *
   * @return bool
   */
  private function inArrayAny($needles, $haystack) {
    return !empty(array_intersect($needles, $haystack));
  }

}
