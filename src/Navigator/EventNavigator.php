<?php

/*
 * This file is part of the Ivory Serializer package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\Serializer\Navigator;

use Ivory\Serializer\Context\ContextInterface;
use Ivory\Serializer\Direction;
use Ivory\Serializer\Event\PostDeserializeEvent;
use Ivory\Serializer\Event\PostSerializeEvent;
use Ivory\Serializer\Event\PreDeserializeEvent;
use Ivory\Serializer\Event\PreSerializeEvent;
use Ivory\Serializer\Event\SerializerEvents;
use Ivory\Serializer\Mapping\TypeMetadataInterface;
use Ivory\Serializer\Type\Guesser\TypeGuesser;
use Ivory\Serializer\Type\Guesser\TypeGuesserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class EventNavigator implements NavigatorInterface
{
    /**
     * @var NavigatorInterface
     */
    private $navigator;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var TypeGuesserInterface
     */
    private $typeGuesser;

    /**
     * @param NavigatorInterface        $navigator
     * @param EventDispatcherInterface  $dispatcher
     * @param TypeGuesserInterface|null $typeGuesser
     */
    public function __construct(
        NavigatorInterface $navigator,
        EventDispatcherInterface $dispatcher,
        TypeGuesserInterface $typeGuesser = null
    ) {
        $this->navigator = $navigator;
        $this->dispatcher = $dispatcher;
        $this->typeGuesser = $typeGuesser ?: new TypeGuesser();
    }

    /**
     * {@inheritdoc}
     */
    public function navigate($data, ContextInterface $context, TypeMetadataInterface $type = null)
    {
        $type = $type ?: $this->typeGuesser->guess($data);
        $serialization = Direction::SERIALIZATION === $context->getDirection();

        if ($serialization) {
            $this->dispatcher->dispatch(
                $event = new PreSerializeEvent($data, $type, $context),
                SerializerEvents::PRE_SERIALIZE
            );
        } else {
            $this->dispatcher->dispatch(
                $event = new PreDeserializeEvent($data, $type, $context),
                SerializerEvents::PRE_DESERIALIZE
            );
        }

        $result = $this->navigator->navigate($data = $event->getData(), $context, $type = $event->getType());

        if ($serialization) {
            $this->dispatcher->dispatch(
                new PostSerializeEvent($data, $type, $context),
                SerializerEvents::POST_SERIALIZE
            );
        } else {
            $this->dispatcher->dispatch(
                new PostDeserializeEvent($result, $type, $context),
                SerializerEvents::POST_DESERIALIZE
            );
        }

        return $result;
    }
}
