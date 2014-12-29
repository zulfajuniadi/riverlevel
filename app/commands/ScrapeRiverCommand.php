<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Goutte\Client;
use Carbon\Carbon;

class ScrapeRiverCommand extends Command {

	protected function extract($conf, $crawler)
	{
		$crawler->filter('tr[height=30]')->each(function ($tr) use ($conf) {
			if(count($tds = $tr->filter('td')) == 10) {
				$river = new River();
				$river->station_id    = (int) $this->trimNonAscii($tds->eq(0)->text());
				$river->state         = $conf['name'];
				$river->district      = $this->trimNonAscii($tds->eq(2)->text());
				$river->name          = $this->trimNonAscii($tds->eq(1)->text());
				$river->basin         = $this->trimNonAscii($tds->eq(3)->text());
				$river->last_updated  = $this->parseDate($tds->eq(4)->text());
				$river->current_level = str_replace(',', '', $this->trimNonAscii($tds->eq(5)->text()));
				$river->normal_level  = str_replace(',', '', $this->trimNonAscii($tds->eq(6)->text()));
				$river->alert_level   = str_replace(',', '', $this->trimNonAscii($tds->eq(7)->text()));
				$river->warning_level = str_replace(',', '', $this->trimNonAscii($tds->eq(8)->text()));
				$river->danger_level  = str_replace(',', '', $this->trimNonAscii($tds->eq(9)->text()));
				if      ($river->current_level < $river->alert_level)  $river->status  = 'normal';
				else if ($river->current_level < $river->warning_level) $river->status = 'alert';
				else if ($river->current_level < $river->danger_level)  $river->status = 'warning';
				else    $river->status = 'danger';
				$river->save();
			}
		});
	}

	protected function tryServer($url, $conf)
	{
		$crawler = $this->client->request('GET', $url, [
			'timeout' => 10
		]);
		$this->extract($conf, $crawler);
	}

	protected function getHosts()
	{
		return [
			'http://infobanjir2.water.gov.my/waterlevel_page.cfm?state=', 
			'http://infobanjir.water.gov.my/waterlevel_page.cfm?state='
		];
	}

	protected function getStates()
	{
        return [
            'PLS' => [
            	'name' => 'Perlis',
            	'pages' => 1
            ],
            'KDH' => [
            	'name' => 'Kedah',
            	'pages' => 1
            ],
            'PNG' => [
            	'name' => 'Pulau Pinang',
            	'pages' => 1
            ],
            'PRK' => [
            	'name' => 'Perak',
            	'pages' => 2
            ],
            'SEL' => [
            	'name' => 'Selangor',
            	'pages' => 4
            ],
            'WLH' => [
            	'name' => 'Kuala Lumpur',
            	'pages' => 1
            ],
            'NSN' => [
            	'name' => 'Negeri Sembilan',
            	'pages' => 1
            ],
            'MLK' => [
            	'name' => 'Melaka',
            	'pages' => 1
            ],
            'JHR' => [
            	'name' => 'Johor',
            	'pages' => 2
            ],
            'PHG' => [
            	'name' => 'Pahang',
            	'pages' => 2
            ],
            'TRG' => [
            	'name' => 'Terengganu',
            	'pages' => 1
            ],
            'KEL' => [
            	'name' => 'Kelantan',
            	'pages' => 1
            ],
            'SRK' => [
            	'name' => 'Sarawak',
            	'pages' => 3
            ],
            'SAB' => [
            	'name' => 'Sabah',
            	'pages' => 1
            ],
        ];
	}

    protected function trimNonAscii($text)
    {
        $text = preg_replace('/[[:^print:]]/', '', Str::ascii($text));
        return trim($text);
    }

    protected function parseDate($text) {
    	if(strstr($text = $this->trimNonAscii($text), 'Off-line'))
    		return '0000-00-00 00:00:00';
    	try {
		    return Carbon::createFromFormat('d/m/Y - H:i', $text, Config::get('app.timezone'))->toDateTimeString();
    	} catch (Exception $e) {
    		return '0000-00-00 00:00:00';
    	}
    }

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'scrape:river';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Run Malaysian River Level Scraping';

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			// array('example', InputArgument::REQUIRED, 'An example argument.'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			// array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
		);
	}

	public function fire()
	{
		$servers = $this->getHosts();
		$pages = [
			1 => [''],
			2 => ['', '&rows=1&rowcheck=1'],
			3 => ['', '&rows=1&rowcheck=1', '&rows=16&rowcheck=1'],
			4 => ['', '&rows=1&rowcheck=1', '&rows=16&rowcheck=1', '&rows=31&rowcheck=1'],
		];
		foreach ($this->getStates() as $code => $conf) {
			foreach ($pages[$conf['pages']] as $index => $addon) {
				foreach ($servers as $server) {
					$url = $server . $code . $addon;
					try {
						$this->info('Trying: ' . $url);
						$this->tryServer($url, $conf);
						break;
					} catch (Exception $e) {
						$this->error('Failed: ' . $url);
					}
				}
			}
			Cache::forget('rivers.states.' . $conf['name']);
		}
		Cache::forget('rivers.alerts');
	}

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->client = new Client();
	}

}
