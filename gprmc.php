<?php
/**
* GPRMC解析
*
* 标准GPRMC报文:
*
* 0      1          2           4            6      8      9      10    END
* |      |          |           |            |      |      |      |    |
* $GPRMC,161229.487,A,3723.2475,N,12158.3416,W,0.13,309.62,120598, ,*10<CR><LF>
*                     |           |            |                    |
*                     3           5            7                    11
* 0:  GPRMC报头
* 1:  标准定位时间  hhmmss.sss
* 2:  状态          A可用, V不可用
* 3:  纬度          ddmm.mmmm
* 4:  南北半球      N/S
* 5:  经度          dddmm.mmmm
* 6:  东西半球      E/W
* 7:  对地速度      节 0.0 ~ 1851.8
* 8:  对地方向      度 000.0 ~ 359.9 (以正北为参考基准)
* 9:  日期          ddmmyy
* 10: 磁偏角        ?
* 11: 校验码
*
* 本地报文:
* $GPRMC,081412.00,A,3147.95419,N,11709.62783,E,0.000,347.53,180113,,,A*6B
*
*
* Demo:
*
* $rmc = new GPRMC('$GPRMC,161229.487,A,3723.2475,N,12158.3416,W,0.13,309.62,120598, ,*10<CR><LF>');
* print_r($rmc->RMC);
* echo "STATUS: " . $rmc->status;
* echo "DATE: " . $rmc->datetime;
* echo "LAT: " . $rmc->lat;
* echo "LONG: " . $rmc->long;
* echo "SPEED: " . $rmc->speed;
* echo "DIRECT: " . $rmc->direction;
*/
class GPRMC
{
  public $RMCmeta;

  public $RMC;
  public $status;
  public $timestamp;
  public $datetime;
  public $lat;
  public $long;
  public $speed;
  public $direction;
  public $angle;

  function __construct($statement)
  {
    $this->RMCmeta = $meta = explode(',', $statement);
    if(count($meta) < 12 && $meta[0] != '$GPRMC')
      return;

    $this->RMC = array();
    $this->RMC['status'] = $this->status = $meta[2];
    $this->RMC['timestamp'] = $this->timestamp = $this->_timestamp($meta[1], $meta[9]);
    $this->RMC['datetime'] = $this->datetime = date('Y-m-d H:i:s', $this->timestamp);
    $this->RMC['lat'] = $this->lat = $this->degree2decimal($meta[3], $meta[4]);
    $this->RMC['long'] = $this->long = $this->degree2decimal($meta[5], $meta[6]);
    $this->RMC['speed'] = $this->speed = $this->_speed($meta[7]);
    $this->RMC['direction'] = $this->direction = $this->_direction($meta[8]);
    $this->RMC['angle'] = $this->angle = $meta[8];
  }

  private function degree2decimal($deg_coord, $direction, $precision = 8)
  {
    $degree = (int) ($deg_coord / 100); //simple way
    $minutes = $deg_coord - ($degree * 100);
    $dotdegree = $minutes / 60;
    $decimal = $degree + $dotdegree;
    //South latitudes and West longitudes need to return a negative result
    if (($direction == "S") or ($direction == "W")) {
      $decimal = $decimal * (-1);
    }
    $decimal = number_format($decimal, $precision, '.', ''); //truncate decimal to $precision places
    return $decimal;
  }

  private function _timestamp($hhmmss, $ddmmyy)
  {
    $hour = substr($hhmmss, 0, 2);
    $minute = substr($hhmmss, 2, 2);
    $second = substr($hhmmss, 4, 2);
    $day = substr($ddmmyy, 0, 2);
    $month =  substr($ddmmyy, 2, 2);
    $year = substr($ddmmyy, 4, 2) + 2000;
    return strtotime("$year-$month-$day $hour:$minute:$second") + 28800;
    //return mktime($hour, $minute, $second, $day, $month, $year);
  }

  private function _speed($knots)
  {
    return $knots * 1.852;
  }

  private function _direction($dgree)
  {
    if($this->speed == 0)
      return '停止';

    $fangxiang = array(
      '北',      // 0
      '北偏东',  // 1
      '东北',    // 2
      '东偏北',  // 3
      '东',      // 4
      '东偏南',  // 5
      '东南',    // 6
      '南偏东',  // 7
      '南',      // 8
      '南偏西',  // 9
      '西南',    // 10
      '西偏南',  // 11
      '西',      // 12
      '西偏北',  // 13
      '西北',    // 14
      '北偏西',  // 15
      '北',      // 16
    );

    $qujian = array(
      array(0, 11.25),        // 0
      array(11.25, 33.75),    // 1
      array(33.75, 56.25),    // 2
      array(56.25, 78.75),    // 3
      array(78.75, 101.25),   // 4
      array(101.25, 123.75),  // 5
      array(123.75, 146.25),  // 6
      array(146.25, 168.75),  // 7
      array(168.75, 191.25),  // 8
      array(191.25, 213.75),  // 9
      array(213.75, 236.25),  // 10
      array(236.25, 258.75),  // 11
      array(258.75, 281.25),  // 12
      array(281.25, 303.75),  // 13
      array(303.75, 326.25),  // 14
      array(326.25, 348.75),  // 15
      array(348.75, 360),     // 16
    );

    foreach ($qujian as $key => $value) {
      if($dgree >= $value[0] && $dgree <= $value[1])
        return $fangxiang[$key];
    }
  }
}
