<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HomeController extends AbstractController
{
    private $client;
    public $data = [
        'items' => [
            '42' => [
                'currency' => 'EUR',
                'price' => 49.99,
                'quantity' => 1
            ],
            '55' => [
                'currency' => 'USD',
                'price' => 12,
                'quantity' => 3
            ],
        ],
        'checkoutCurrency' => "EUR"
    ];

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @Route("/",name="home")
     * @return Response
     */
    public function indexAction()
    {
        $content = $this->fetchRatesInformation();

        $checkoutCurrency = $this->data['checkoutCurrency'];

        $total = 0;
        foreach ($this->data['items'] as $items) {
            $sum = 0;
            if (is_numeric($items['price']) && is_numeric($items['quantity'])) {
                $result = $items['price'] * $items['quantity'];
                if ($items['currency'] == $checkoutCurrency) {
                    $sum += $result;
                } else {
                    if ($checkoutCurrency == 'USD') {
                        foreach ($content as $k => $v) {
                            if ($k == $items['currency']) {
                                $sum += $result / $v;
                            }
                        }
                    } else {
                        foreach ($content as $k => $v) {
                            if ($k == $items['currency']) {
                                $result = $result / $v;
                            }
                        }
                        foreach ($content as $k => $v) {
                            if ($k == $checkoutCurrency) {
                                $sum += $result * $v;
                            }
                        }
                    }
                }
            }
            $sum = round($sum, 2);
            $total += $sum;
        }

        $resultJson = [
            'checkoutPrice' => $total,
            'checkoutCurrency' => $checkoutCurrency
        ];

        $resultJson = json_encode($resultJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $response = new Response($resultJson);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function fetchRatesInformation(): array
    {
        $response = $this->client->request(
            'GET',
            'https://openexchangerates.org/api/latest.json?app_id=f26568e181084a4b853a9e06a5cd6136'
        );

        $content = $response->getContent();
        $content = $response->toArray();

        return $content['rates'];
    }
}
