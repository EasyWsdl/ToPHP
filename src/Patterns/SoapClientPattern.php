<?php declare(strict_types=1);


namespace EasyWsdl\ToPHP\Patterns;


class SoapClientPattern
{
    /** @var array */
    private static $renamedProperties;
    /** @var array */
    private static $classmap;
    /** @var array */
    protected $options;

    public function __construct(string $wsdl, ?array $options = [])
    {
        self::$classmap = self::loadClassMap();
        self::$renamedProperties = self::loadRenamedProperties();
        $this->options = self::normalizeOptions($options);
        parent::__construct($wsdl, $this->options);
    }

    public function callMethod(TypeHint $arguments): ReturnType
    {
        $arguments = $this->encodeEntities($arguments);
        $request = $this->__soapCall('callMethod', [$arguments]);
        $this->decodeEntities($arguments);
        $request = $this->decodeEntities($request);

        return $request;
    }

    public function callMethodWithoutRequest(): ReturnType
    {
        $request = $this->__soapCall('callMethod', []);
        $request = $this->decodeEntities($request);

        return $request;
    }


    protected static function normalizeOptions(?array $options): array
    {
        $options['classmap'] = self::$classmap;

        return $options;
    }

    private function decodeEntities($entity)
    {
        foreach (get_object_vars($entity) as $propyName => $propy)
        {
            if (is_object($propy))
            {
                $propy = $this->decodeEntities($propy);
            }
            if (is_array($propy))
            {
                foreach ($propy as $key => $prop)
                {
                    if (is_object($prop))
                    {
                        $propy[$key] = $this->decodeEntities($prop);
                    }
                }
            }
            if ($key = array_search($propyName, self::$renamedProperties))
            {
                $entity->{$key} = $propy;
                unset($entity->{$propyName});
            }
        }

        return $entity;
    }

    private function encodeEntities($entity)
    {
        foreach (get_object_vars($entity) as $propyName => $propy)
        {
            if (is_object($propy))
            {
                $propy = $this->encodeEntities($propy);
            }
            if (array_key_exists($propyName, self::$renamedProperties))
            {
                $entity->{self::$renamedProperties[$propyName]} = $propy;
                unset($entity->{$propyName});
            }
        }

        return $entity;
    }
}