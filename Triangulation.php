<?php


namespace Triangulation;


class Triangulation
{

    private $ping_servers = [];

    private $wanted_point = null;

    private $step = 10;
    private $sq_num;
    private $min_point;

    private $error_val = 0.0001;

    private $area = [];

    private $i;

    public function __construct($ping_servers = null, $wanted_point = null) {
        require_once 'GeoCalculations.php';
        if ($ping_servers !== null && $wanted_point !== null) {
            $this->addServers($ping_servers);
            $this->addWantedPoint($wanted_point);
        }

        $this->sq_num = 90 / $this->step;
        $this->min_point = new GeoPoint();

        for ($i = -$this->sq_num; $i <= $this->sq_num; $i++) {
            for ($j = -$this->sq_num; $j <= $this->sq_num; $j++) {
                $this->area[$i][$j]['point'] = new GeoPoint();
            }
        }
    }

    private function addServers(array $ping_servers) {
        $this->ping_servers = [];
        if ($this->checkServers($ping_servers)) {
            $this->ping_servers = $ping_servers;
        }
    }

    private function addWantedPoint($wanted_point) {
        if ($wanted_point instanceof GeoPoint) {
            $this->wanted_point = $wanted_point;
        }
    }

    private function checkServers($servers) {
        if (!is_array($servers)) return false;
        foreach ($servers as $server) {
            if (!($server instanceof PingServer)) return false;
        }
        return true;
    }

    public function run() {
        if ($this->wanted_point instanceof GeoPoint && $this->checkServers($this->ping_servers)) {

            $this->min_point->longitude = 0;
            $this->min_point->latitude = 0;
            $coordinates = $this->findCrossingSquare();

            $this->wanted_point->latitude = $coordinates['lat'];
            $this->wanted_point->longitude = $coordinates['lon'];
        }
    }

    private function calculateDiv($i) {
        $div = 0;
        if ($i instanceof GeoPoint) {
            $point = $i;
        }
        foreach ($this->ping_servers as $server) {
            $div += abs(GeoCalculations::DistanceBetweenPoints($point, $server) - $server->getDistanceToTarget());
        }
        return $div;
    }

    private function findCrossingSquare () {
        $this->i = 0;
        while ($this->step > $this->error_val) {
            $this->findSquare();
            $this->changeStep();
            $this->i++;
        }

        return [
            'lon' => $this->min_point->longitude,
            'lat' => $this->min_point->latitude,
        ];
    }

    private function changeStep() {
        $this->step = $this->step * 0.5;
    }

    private function findSquare() {
        for ($i = -$this->sq_num; $i <= $this->sq_num; $i++) {
            for ($j = -$this->sq_num; $j <= $this->sq_num; $j++) {
                $this->area[$i][$j]['point']->longitude = 2 * $i * $this->step + $this->min_point->longitude;
                $this->area[$i][$j]['point']->latitude = $j * $this->step + $this->min_point->latitude;
                $this->area[$i][$j]['div'] = $this->calculateDiv($this->area[$i][$j]['point']);
            }
        }
        $min_div = 10 ** 10;
        for ($i = -$this->sq_num; $i <= $this->sq_num; $i++) {
            for ($j = -$this->sq_num; $j <= $this->sq_num; $j++) {
                if ($this->area[$i][$j]['div'] < $min_div) {
                    $min_div = $this->area[$i][$j]['div'];
                    $this->min_point->longitude = $this->area[$i][$j]['point']->longitude;
                    $this->min_point->latitude = $this->area[$i][$j]['point']->latitude;
                }
            }
        }
    }

}