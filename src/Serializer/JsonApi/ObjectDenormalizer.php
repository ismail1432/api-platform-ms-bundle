<?php

namespace Mtarld\ApiPlatformMsBundle\Serializer\JsonApi;

use ApiPlatform\Core\JsonApi\Serializer\ReservedAttributeNameConverter;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;

// Help opcache.preload discover always-needed symbols
class_exists(ReservedAttributeNameConverter::class);

/**
 * @final @internal
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
class ObjectDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    private const ALREADY_CALLED = 'jsonapi_object_denormalizer_already_called';

    use JsonApiDenormalizerTrait;
    use DenormalizerAwareTrait;

    /**
     * @param string $type
     * @param string $format
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        if (isset($data['data'])) {
            $data = $data['data'];
        }

        if (isset($data['attributes'])) {
            $data = $data['attributes'];
        }

        $data = $this->convertReservedAttributeNames($data);

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    /**
     * @param string $type
     * @param string $format
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return false === ($context[self::ALREADY_CALLED] ?? false) && $this->getFormat() === $format;
    }

    private function convertReservedAttributeNames(array $data): array
    {
        $reservedAttributes = array_flip(ReservedAttributeNameConverter::JSON_API_RESERVED_ATTRIBUTES);

        foreach ($data as $key => $value) {
            // Collection
            if (is_array($value)) {
                $data[$key] = $this->convertReservedAttributeNames($data[$key]);
            }

            // Item
            if (isset($reservedAttributes[$key])) {
                $data[$reservedAttributes[$key]] = $value;
                unset($data[$key]);
            }
        }

        return $data;
    }
}
