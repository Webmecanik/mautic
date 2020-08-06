<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOwnerManagerBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use MauticPlugin\MauticOwnerManagerBundle\Entity\OwnerManager;
use MauticPlugin\MauticOwnerManagerBundle\Entity\Point;
use MauticPlugin\MauticOwnerManagerBundle\Model\OwnerManagerModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OwnerManagerController extends AbstractFormController
{
    /**
     * @param int $page
     *
     * @return JsonResponse|Response
     */
    public function indexAction($page = 1)
    {
        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted([
            'ownermanager:ownermanager:view',
            'ownermanager:ownermanager:create',
            'ownermanager:ownermanager:edit',
            'ownermanager:ownermanager:delete',
            'ownermanager:ownermanager:publish',
        ], 'RETURN_ARRAY');

        if (!$permissions['ownermanager:ownermanager:view']) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        //set limits
        $limit = $this->get('session')->get('mautic.ownermanager.limit', $this->coreParametersHelper->getParameter('default_pagelimit'));
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $this->get('session')->get('mautic.ownermanager.filter', ''));
        $this->get('session')->set('mautic.ownermanager.filter', $search);

        $filter     = ['string' => $search, 'force' => []];
        $orderBy    = $this->get('session')->get('mautic.ownermanager.orderby', 'p.name');
        $orderByDir = $this->get('session')->get('mautic.ownermanager.orderbydir', 'ASC');

        $ownerManagers = $this->getModel('ownermanager')->getEntities([
            'start'      => $start,
            'limit'      => $limit,
            'filter'     => $filter,
            'orderBy'    => $orderBy,
            'orderByDir' => $orderByDir,
        ]);

        $count = count($ownerManagers);
        if ($count && $count < ($start + 1)) {
            $lastPage = (1 === $count) ? 1 : (ceil($count / $limit)) ?: 1;
            $this->get('session')->set('mautic.ownermanager.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_ownermanager_index', ['page' => $lastPage]);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $lastPage],
                'contentTemplate' => 'MauticOwnerManagerBundle:OwnerManager:index',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_ownermanager_index',
                    'mauticContent' => 'point',
                ],
            ]);
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->get('session')->set('mautic.ownermanager.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        //get the list of actions
        $actions = $this->getModel('ownermanager')->getOwnerManagerActions();

        return $this->delegateView([
            'viewParameters' => [
                'searchValue' => $search,
                'items'       => $ownerManagers,
                'actions'     => $actions['actions'],
                'page'        => $page,
                'limit'       => $limit,
                'permissions' => $permissions,
                'tmpl'        => $tmpl,
            ],
            'contentTemplate' => 'MauticOwnerManagerBundle:OwnerManager:list.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_ownermanager_index',
                'mauticContent' => 'ownermanager',
                'route'         => $this->generateUrl('mautic_ownermanager_index', ['page' => $page]),
            ],
        ]);
    }

    /**
     * Generates new form and processes post data.
     *
     * @param \MauticPlugin\MauticOwnerManagerBundle\Entity\Point $entity
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function newAction($entity = null)
    {
        $model = $this->getModel('ownermanager');

        if (!($entity instanceof OwnerManager)) {
            /** @var \MauticPlugin\MauticOwnerManagerBundle\Entity\OwnerManager $entity */
            $entity = $model->getEntity();
        }

        if (!$this->get('mautic.security')->isGranted('ownermanager:ownermanager:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page = $this->get('session')->get('mautic.ownermanager.page', 1);

        $actionType = ('POST' == $this->request->getMethod()) ? $this->request->request->get('owner_manager', '')['type'] : '';

        $action  = $this->generateUrl('mautic_ownermanager_action', ['objectAction' => 'new']);
        $actions = $model->getOwnerManagerActions();
        $form    = $model->createForm($entity, $this->get('form.factory'), $action, [
            'ownermanagerActions' => $actions,
            'actionType'          => $actionType,
        ]);
        $viewParameters = ['page' => $page];

        ///Check for a submitted form and process it
        if ('POST' == $this->request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlash('mautic.core.notice.created', [
                        '%name%'      => $entity->getName(),
                        '%menu_link%' => 'mautic_ownermanager_index',
                        '%url%'       => $this->generateUrl('mautic_ownermanager_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]),
                    ]);

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        $returnUrl = $this->generateUrl('mautic_ownermanager_index', $viewParameters);
                        $template  = 'MauticOwnerManagerBundle:OwnerManager:index';
                    } else {
                        //return edit view so that all the session stuff is loaded
                        return $this->editAction($entity->getId(), true);
                    }
                }
            } else {
                $returnUrl = $this->generateUrl('mautic_ownermanager_index', $viewParameters);
                $template  = 'MauticOwnerManagerBundle:OwnerManager:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect([
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $template,
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_ownermanager_index',
                        'mauticContent' => 'point',
                    ],
                ]);
            }
        }

        $themes = ['MauticPointBundle:FormTheme\Action'];
        if ($actionType && !empty($actions['actions'][$actionType]['formTheme'])) {
            $themes[] = $actions['actions'][$actionType]['formTheme'];
        }

        return $this->delegateView([
            'viewParameters' => [
                'tmpl'    => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'entity'  => $entity,
                'form'    => $this->setFormTheme($form, 'MauticOwnerManagerBundle:OwnerManager:form.html.php', $themes),
                'actions' => $actions['actions'],
            ],
            'contentTemplate' => 'MauticOwnerManagerBundle:OwnerManager:form.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_ownermanager_index',
                'mauticContent' => 'point',
                'route'         => $this->generateUrl('mautic_ownermanager_action', [
                        'objectAction' => (!empty($valid) ? 'edit' : 'new'), //valid means a new form was applied
                        'objectId'     => $entity->getId(),
                    ]
                ),
            ],
        ]);
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction($objectId, $ignorePost = false)
    {
        /** @var OwnerManagerModel $model */
        $model  = $this->getModel('ownermanager');
        $entity = $model->getEntity($objectId);

        //set the page we came from
        $page = $this->get('session')->get('mautic.ownermanager.page', 1);

        $viewParameters = ['page' => $page];

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_ownermanager_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParameters,
            'contentTemplate' => 'MauticOwnerManagerBundle:OwnerManager:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_ownermanager_index',
                'mauticContent' => 'point',
            ],
        ];

        //form not found
        if (null === $entity) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.ownermanager.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ])
            );
        } elseif (!$this->get('mautic.security')->isGranted('ownermanager:ownermanager:edit')) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'point');
        }

        $actionType = ('POST' == $this->request->getMethod()) ? $this->request->request->get('owner_manager', '')['type'] : $entity->getType();

        $action  = $this->generateUrl('mautic_ownermanager_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $actions = $model->getOwnerManagerActions();
        $form    = $model->createForm($entity, $this->get('form.factory'), $action, [
            'ownermanagerActions' => $actions,
            'actionType'          => $actionType,
        ]);

        ///Check for a submitted form and process it
        if (!$ignorePost && 'POST' == $this->request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        $returnUrl = $this->generateUrl('mautic_ownermanager_index', $viewParameters);
                        $template  = 'MauticOwnerManagerBundle:OwnerManager:index';
                    }
                }
            } else {
                //unlock the entity
                $model->unlockEntity($entity);

                $returnUrl = $this->generateUrl('mautic_ownermanager_index', $viewParameters);
                $template  = 'MauticOwnerManagerBundle:OwnerManager:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(
                    array_merge($postActionVars, [
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template,
                    ])
                );
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);
        }

        $themes = ['MauticOwnerManagerBundle:FormTheme\Action'];
        if (!empty($actions['actions'][$actionType]['formTheme'])) {
            $themes[] = $actions['actions'][$actionType]['formTheme'];
        }

        return $this->delegateView([
            'viewParameters' => [
                'tmpl'    => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'entity'  => $entity,
                'form'    => $this->setFormTheme($form, 'MauticOwnerManagerBundle:OwnerManager:form.html.php', $themes),
                'actions' => $actions['actions'],
            ],
            'contentTemplate' => 'MauticOwnerManagerBundle:OwnerManager:form.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_ownermanager_index',
                'mauticContent' => 'ownerManager',
                'route'         => $this->generateUrl('mautic_ownermanager_action', [
                        'objectAction' => 'edit',
                        'objectId'     => $entity->getId(),
                    ]
                ),
            ],
        ]);
    }

    /**
     * Clone an entity.
     *
     * @param int $objectId
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction($objectId)
    {
        $model  = $this->getModel('ownermanager');
        $entity = $model->getEntity($objectId);

        if (null != $entity) {
            if (!$this->get('mautic.security')->isGranted('ownermanager:ownermanager:create')) {
                return $this->accessDenied();
            }

            $entity = clone $entity;
            $entity->setIsPublished(false);
        }

        return $this->newAction($entity);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        $page      = $this->get('session')->get('mautic.ownermanager.page', 1);
        $returnUrl = $this->generateUrl('mautic_ownermanager_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticOwnerManagerBundle:OwnerManager:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_ownermanager_index',
                'mauticContent' => 'point',
            ],
        ];

        if ('POST' == $this->request->getMethod()) {
            $model  = $this->getModel('ownermanager');
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.ownermanager.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->get('mautic.security')->isGranted('ownermanager:ownermanager:delete')) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'point');
            }

            $model->deleteEntity($entity);

            $identifier = $this->get('translator')->trans($entity->getName());
            $flashes[]  = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $identifier,
                    '%id%'   => $objectId,
                ],
            ];
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * Deletes a group of entities.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        $page      = $this->get('session')->get('mautic.ownermanager.page', 1);
        $returnUrl = $this->generateUrl('mautic_ownermanager_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticOwnerManagerBundle:OwnerManager:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_ownermanager_index',
                'mauticContent' => 'point',
            ],
        ];

        if ('POST' == $this->request->getMethod()) {
            $model     = $this->getModel('ownermanager');
            $ids       = json_decode($this->request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.ownermanager.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->get('mautic.security')->isGranted('ownermanager:ownermanager:delete')) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'point', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.ownermanager.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => count($entities),
                    ],
                ];
            }
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }
}
