<?php

namespace Borislav\Clickhouse\Serializer\Normalizer;

use Borislav\Clickhouse\Entity\Interfaces\ClickHouseEntityInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ClickHouseEntityNormalizer  implements ContextAwareNormalizerInterface
{
	public function __construct(
		private ObjectNormalizer $normalizer
	) {}
	
	/**
	 * @param             $data
	 * @param string|null $format
	 * @param array       $context
	 *
	 * @return bool
	 */
	public function supportsNormalization($data, string $format = null, array $context = [])
	{
		if (is_object($data) || (is_string($data) && class_exists($data))) {
			if ($classes = class_implements($data)) {
				return in_array(ClickHouseEntityInterface::class, $classes);
			}
		}
		
		return false;
	}
	
	/**
	 * @param ClickHouseEntityInterface $object
	 * @param string|null               $format
	 * @param array                     $context
	 *
	 * @return array|\ArrayObject|bool|float|int|mixed|string|null
	 *
	 * @throws ExceptionInterface
	 */
	public function normalize($object, string $format = null, array $context = [])
	{
		$data = $this->normalizer->normalize($object);
		
		$mergeData = [];
		if (array_key_exists('from', $data)) {
			$mergeData['from'] = $object->getFrom()->format('Y-m-d H:i:s');
		}
		if (array_key_exists('to', $data)) {
			$mergeData['to'] = $object->getTo()->format('Y-m-d H:i:s');
		}
		if (is_null($object->getId())) {
			$mergeData['id'] = Uuid::uuid4();
		}
		$mergeData['hasBeenCalculated'] = (int) $object->isHasBeenCalculated();
		
		return array_merge($data, $mergeData);
	}
}
