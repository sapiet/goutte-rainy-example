<?php

require_once 'vendor/autoload.php';

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class Rainy
{
    const LINES_TO_SKIP = 2;

    private $client;
    private $page;

    public function __construct(string $page)
    {
        $this->client = new Client();
        $this->page = $page;
    }

    public function getTableArray()
    {
        $crawler = $this->client->request('GET', $this->page);
        $node = $crawler->filter('table tr td.Style1 center table table')->first();

        $results = [];

        $node->filter('tr')->each(function(Crawler $tableRow) use (&$results) {
            $row = [];

            $tableRow->filter('td')->each(function(Crawler $cell) use (&$row) {
                $row[] = $cell->text();
            });

            $results[] = $row;
        });

        return $results;
    }

    public function parseDays(array $tableArray): array
    {
        $days = [];
        $currentDay = [];
        $tableArray = array_slice($tableArray, self::LINES_TO_SKIP);

        foreach($tableArray as $tableItem) {
            if (count($tableItem) === 11) {
                if (!empty($currentDay)) {
                    $days[] = $currentDay;
                }

                $currentDay = [
                    'day' => $tableItem[0],
                    'rainy' => false,
                    'hours' => []
                ];

                $tableItem = array_slice($tableItem, 1);
            }

            $currentDay['hours'][$tableItem[0]] = $tableItem[6];

            if ($tableItem[6] !== '--') {
                $currentDay['rainy'] = true;
            }
        }

        $days[] = $currentDay;

        return $days;
    }

    public function process(): array
    {
        $tableArray = $this->getTableArray();
        return $this->parseDays($tableArray);
    }

    public function tellUs(array $days): void
    {
        foreach ($days as $day) {
            echo $day['day'].' : '.($day['rainy'] ? 'oui' : 'non').PHP_EOL;
        }
    }
}



$rainy = new Rainy('http://www.meteociel.fr/previsions/20733/marcq_en_baroeul.htm');
$days = $rainy->process();
echo 'Il pleut ?'.PHP_EOL;
$rainy->tellUs($days);
