<?php

/**
 * @see       https://github.com/laminas/laminas-form for the canonical source repository
 * @copyright https://github.com/laminas/laminas-form/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-form/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Form\Annotation;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use ReflectionClass;

/**
 * Base annotations listener.
 *
 * Provides an implementation of detach() that should work with any listener.
 * Also provides listeners for the "Name" annotation -- handleNameAnnotation()
 * will listen for the "Name" annotation, while discoverFallbackName() listens
 * on the "discoverName" event and will use the class or property name, as
 * discovered via reflection, if no other annotation has provided the name
 * already.
 *
 * @category   Laminas
 * @package    Laminas_Form
 * @subpackage Annotation
 */
abstract class AbstractAnnotationsListener implements ListenerAggregateInterface
{
    /**
     * @var \Laminas\Stdlib\CallbackHandler[]
     */
    protected $listeners = array();

    /**
     * Detach listeners
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if (false !== $events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * Attempt to discover a name set via annotation
     *
     * @param  \Laminas\EventManager\EventInterface $e
     * @return false|string
     */
    public function handleNameAnnotation($e)
    {
        $annotations = $e->getParam('annotations');

        if (!$annotations->hasAnnotation('Laminas\Form\Annotation\Name')) {
            return false;
        }

        foreach ($annotations as $annotation) {
            if (!$annotation instanceof Name) {
                continue;
            }
            return $annotation->getName();
        }

        return false;
    }

    /**
     * Discover the fallback name via reflection
     *
     * @param  \Laminas\EventManager\EventInterface $e
     * @return string
     */
    public function discoverFallbackName($e)
    {
        $reflection = $e->getParam('reflection');
        if ($reflection instanceof ReflectionClass) {
            return $reflection->getShortName();
        }

        return $reflection->getName();
    }
}
