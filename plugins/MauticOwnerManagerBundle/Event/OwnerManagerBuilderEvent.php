<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOwnerManagerBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\Translation\TranslatorInterface;

class OwnerManagerBuilderEvent extends Event
{
    /**
     * @var array
     */
    private $actions = [];

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Adds an action to the list of available .
     *
     * @param string $key    - a unique identifier; it is recommended that it be namespaced i.e. lead.action
     * @param array  $action - can contain the following keys:
     *                       'label'           => (required) what to display in the list
     *                       'description'     => (optional) short description of event
     *                       'template'        => (optional) template to use for the action's HTML in the owner manager builder
     *                       i.e AcmeMyBundle:OwnerManagerAction:theaction.html.php
     *                       'formType'        => (optional) name of the form type SERVICE for the action; will use a default form with owner manager change only
     *                       'formTypeOptions' => (optional) array of options to pass to formType
     *                       'callback'        => (optional) callback function that will be passed when the action is triggered; return true to
     *                       change the configured owners or false to ignore the action
     *                       The callback function can receive the following arguments by name (via ReflectionMethod::invokeArgs())
     *                       Mautic\CoreBundle\Factory\MauticFactory $factory
     *                       Mautic\LeadBundle\Entity\Lead $lead
     *                       $eventDetails - variable sent from firing function to call back function
     *                       array $action = array(
     *                       'id' => int
     *                       'type' => string
     *                       'name' => string
     *                       'properties' => array()
     *                       )
     *
     * @throws InvalidArgumentException
     */
    public function addAction($key, array $action)
    {
        if (array_key_exists($key, $this->actions)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another action. Please use a different key.");
        }

        //check for required keys and that given functions are callable
        $this->verifyComponent(
            ['group', 'label'],
            ['callback'],
            $action
        );

        //Support for old way with callback and new event based system
        //Could be removed after all events will be refactored to events. The key 'eventName' will be mandatory and 'callback' will be removed.
        if (!array_key_exists('callback', $action) && !array_key_exists('eventName', $action)) {
            throw new InvalidArgumentException("One of the 'callback' or 'eventName' has to be provided. Use 'eventName' for new code");
        }

        //translate the label and group
        $action['label'] = $this->translator->trans($action['label']);
        $action['group'] = $this->translator->trans($action['group']);

        $this->actions[$key] = $action;
    }

    /**
     * Get actions.
     *
     * @return array
     */
    public function getActions()
    {
        uasort($this->actions, function ($a, $b) {
            return strnatcasecmp(
                $a['label'], $b['label']);
        });

        return $this->actions;
    }

    /**
     * Gets a list of actions supported by the choice form field.
     *
     * @return array
     */
    public function getActionList()
    {
        $list    = [];
        $actions = $this->getActions();
        foreach ($actions as $k => $a) {
            $list[$k] = $a['label'];
        }

        return $list;
    }

    public function getActionChoices()
    {
        $choices = [];
        $actions = $this->getActions();
        foreach ($this->actions as $k => $c) {
            $choices[$c['group']][$c['label']] = $k;
        }

        return $choices;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function verifyComponent(array $keys, array $methods, array $component)
    {
        foreach ($keys as $k) {
            if (!array_key_exists($k, $component)) {
                throw new InvalidArgumentException("The key, '$k' is missing.");
            }
        }

        foreach ($methods as $m) {
            if (isset($component[$m]) && !is_callable($component[$m], true)) {
                throw new InvalidArgumentException($component[$m].' is not callable.  Please ensure that it exists and that it is a fully qualified namespace.');
            }
        }
    }
}