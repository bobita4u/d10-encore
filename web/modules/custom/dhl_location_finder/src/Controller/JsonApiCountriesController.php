<?php

namespace Drupal\dhl_location_finder\Controller;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;
use \CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use \CommerceGuys\Addressing\Country\CountryRepository;

/**
 * Class JsonApiCountriesController
 * @package Drupal\dhl_location_finder\Controller
 */
class JsonApiCountriesController
{
  /**
   * @return JsonResponse
   */
  public function handleAutocomplete(Request $request)
  {
    $results = [];
    $input = $request->query->get('q');
    if (!$input) {
      return new JsonResponse($results);
    }
    $input = Xss::filter($input);

    $countryRepository = new CountryRepository();
    $options = [];

    // Get all country objects.
    $countries = $countryRepository->getAll();

    if (!empty($countries)) {
      foreach ($countries as $c) {
        if (str_contains($c->getCountryCode(), strtoupper($input))) {
          $options[$c->getCountryCode()] = $c->getName();
          $results[] = [
            'value' => $c->getCountryCode(),
            'label' => $c->getName() . ' (' . $c->getCountryCode() . ')',
          ];
        }
      }
    }

    return new JsonResponse($results);
  }
}
