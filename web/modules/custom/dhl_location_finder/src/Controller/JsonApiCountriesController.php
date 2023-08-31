<?php
/**
 * JsonApiCountriesController.php
 *
 * @author    Babita Neog <bobita4u@gmail.com>
 * @copyright 2023 Babita
 * @license   Licence Name
 * PHP version 8
 */

namespace Drupal\dhl_location_finder\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;
use CommerceGuys\Addressing\Country\CountryRepository;

/**
 * JsonApiCountriesController Class Doc Comment
 *
 * @category Class
 * @package  dhl_location_finder
 * @author   B.N,
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class JsonApiCountriesController
{

    /**
     * Callback method for the autocompletion textbox for country codes list.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
                if (str_contains(strtoupper($c->getName()), strtoupper($input))
                    || str_contains($c->getCountryCode(), strtoupper($input))
                ) {
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
