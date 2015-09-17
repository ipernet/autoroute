<?php

namespace KRDS\Kite\Routing;

class Autoroute
{
    protected $pathInfo;
    protected $psr4Prefix;
    protected $psr4Paths;

    protected $phpExtension		=	'php';
    protected $defaultController	=	'Index';
    protected $defaultAction		=	'index';
    protected $segmentFilterPattern	=	'#^[a-z0-9]+$#i';

    public function __construct($pathInfo, $psr4Prefix, array $psr4Paths, $options = [])
    {
	if( ! empty($options['default_controller']))
	    $this->defaultController	=	$options['default_controller'];

	if( ! empty($options['default_action']))
	    $this->defaultAction	=	$options['default_action'];

	if( ! empty($options['segment_pattern']))
	    $this->segmentFilterPattern	=	$options['segment_pattern'];

	if( ! empty($options['php_extension']))
	    $this->phpExtension		=	$options['php_extension'];

	$this->pathInfo		=	$pathInfo;
	$this->psr4Prefix	=	trim($psr4Prefix, '\\').'\\';
	$this->psr4Paths	=	$psr4Paths;

	return $this;
    }

    public function resolve()
    {
        // Validate segments names
	$segments	=	explode('/', strtolower($this->pathInfo));
	$segments	=	$this->filterSegments($segments);

	// PSR-4 => ucfirst() all segments
	array_walk($segments, function(&$v, $k)
	{
	    $v	=	ucfirst($v);
	});

	$count	=	count($segments);

	// See Doc#resolving
	if($count === 1)
	{
	    foreach($this->psr4Paths as $path)
	    {
		if(is_dir($path.'/'.current($segments)))
		    $segments[]	=	$this->defaultController;
	    }
	}
	else
	{
	    if($count === 0)
		$segments[]	=	$this->defaultController;

	    $sgti	=	implode(DIRECTORY_SEPARATOR, $segments);

	    $found	=	false;

	    foreach($this->psr4Paths as $path)
	    {
		if(is_dir($path.DIRECTORY_SEPARATOR.$sgti))
		{
		    $segments[]	=	$this->defaultController;
		    $found	=	true;
		    break;
		}
	    }

	    if( ! $found)
	    {
		foreach($this->psr4Paths as $path)
		{
		    if(is_file($path.DIRECTORY_SEPARATOR.$sgti.'.'.$this->phpExtension))
		    {
			$found	=	true;
			break;
		    }
		}
	    }

	    if( ! $found)
	    {
		$options['defaultAction']	=	array_pop($segments);

		if(empty($segments))
		    $segments[]	=	$this->defaultController;
	    }
	}

	return [
	    'path'	=>	$this->pathInfo,
	    'class'	=>	$this->psr4Prefix.implode('\\', $segments),
	    'action'	=>	$this->defaultAction
	];
    }

    protected function filterSegments($segments)
    {
	return array_filter($segments, function($v)
	{
	    return ! (empty($v) || preg_match($this->segmentFilterPattern, $v) !== 1);
	});
    }
}
