<?php

class smvcProfiler{
	static public $stats = array();
	static public $fullStackStats = array();
	static private $startTime = 0;
	static private $lastTime = 0;
	static private $ticks = 0;

	/** only static class not instanciable */
	private function __construct(){}

	/**
	* start the profiling
	* @param (int) $ticks declare the ticks directive with according ticks value
	* @return nothing
	*/
	static function start($ticks=1){
		register_tick_function(__class__.'::profile');
		self::$startTime = self::$lastTime = microtime(true);
		self::setTicks($ticks);
	}
	/**
	* stop the profiling
	*/
	static function stop($ticks=0){
		unregister_tick_function(__class__.'::profile');
		self::setTicks($ticks);
	}
	/**
	* same as using declare(ticks=$ticks) but will return previous ticks value
	* @param int $ticks
	* @return int previous ticks value.
	*/
	static function setTicks($ticks=0){
		$res = self::$ticks;
		self::$ticks = (int) $ticks;
		eval('declare(ticks='.self::$ticks.');');
		return $res;
	}
	/**
	* profiling callback method used with register_tick_function
	* this is not intended to be used manually.
	*/
	static function profile(){
		$call = debug_backtrace(false);
		if( count($call)===1 ) // avoid calling profile while already in it
			return;
		$now = microtime(true);
		$t = $now - self::$lastTime ;
		$call = formatted_backtrace('%call',1,null,$call);
		foreach($call as $k=>$c){
			#- $c = strip_tags($c);
			if(!$k){
				self::$stats[$c] = (isset(self::$stats[$c])?self::$stats[$c]:0)+$t;
			}
			self::$fullStackStats[$c] = (isset(self::$fullStackStats[$c])?self::$fullStackStats[$c]+$t:0);
		}
		self::$lastTime = $now;
	}
	/**
	* return the statistics results of the profiling as an array
	* @return array
	*/
	static function results($fullStackMode=false){
		if( empty(self::$stats)){
			return 'Profiler not initialized';
		}
		self::stop();
		$tot = 0;
		$stats = $fullStackMode?self::$fullStackStats : self::$stats;
		if( isset($stats['TOTAL'])){
			unset($stats['TOTAL']);
		}
		foreach($stats as $k=>$t){
			#- $tot += $t;
			$stats[$k] = round($t,4);
		}
		arsort($stats);
		$stats['TOTAL'] = round(self::$lastTime-self::$startTime,4);
		return $stats;
	}
	static function htmlResults($treshold=5){
		$res = self::results();
		$fullRes = self::results(true);
		$tot = $res['TOTAL'];
		foreach( $fullRes as $k=>$v){
			if(! isset($res[$k])){
				$color = '#ddf';
				$real = "N/A";
			}else{
				$realPerc = (bcdiv($res[$k],$tot,4)*100);
				$real = $res[$k]."µsec / $realPerc%";
				$color = $realPerc>$treshold?'#fdd':'#dfd';
			}
			if($k === 'TOTAL')
				$k .=' ( '.count($fullRes).' distinct locations)';
			$out[] = "<tr style=\"background:$color;\"><th>$k</th><td>$v µsec / ".(bcdiv($v,$tot,2)*100)."%</td><td>$real</td></tr>";
		}
		return '<table border="1" cellspacing="0" cellpadding="2" align="center">
			<thead><th>location</th><th>total running time</th><th>real inside time</th></thead>
			'.implode("\n\t",$out).'
			</table>';
	}

}
