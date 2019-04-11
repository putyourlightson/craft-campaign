<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use DateTime;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;

use Craft;
use craft\errors\ElementNotFoundException;
use craft\helpers\DateTimeHelper;
use craft\web\Controller;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\NotFoundHttpException;

/**
 * ContactsController
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class ContactsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws ForbiddenHttpException
     */
    public function init()
    {
        // Require permission
        $this->requirePermission('campaign:contacts');
    }

    /**
     * @param string|null $siteHandle
     *
     * @return Response
     */
    public function actionIndex(string $siteHandle = null): Response
    {
        // Set the current site to the site handle if set
        if ($siteHandle !== null) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if ($site !== null) {
                Craft::$app->getSites()->setCurrentSite($site);
            }
        }

        // Render the template
        return $this->renderTemplate('campaign/contacts/view');
    }

    /**
     * @param int|null $contactId The contact’s ID, if editing an existing contact.
     * @param ContactElement|null $contact The contact being edited, if there were any validation errors.
     *
     * @return Response
     * @throws NotFoundHttpException if the requested contact is not found
     * @throws InvalidConfigException
     */
    public function actionEditContact(int $contactId = null, ContactElement $contact = null): Response
    {
        $variables = [];

        // Get the contact
        // ---------------------------------------------------------------------

        if ($contact === null) {
            if ($contactId !== null) {
                $contact = Campaign::$plugin->contacts->getContactById($contactId);

                if ($contact === null) {
                    throw new NotFoundHttpException(Craft::t('campaign', 'Contact not found.'));
                }
            }
            else {
                $contact = new ContactElement();
                $contact->enabled = true;
            }
        }

        // Set the variables
        // ---------------------------------------------------------------------

        $variables['contactId'] = $contactId;
        $variables['contact'] = $contact;

        // Set the title
        // ---------------------------------------------------------------------

        if ($contactId === null) {
            $variables['title'] = Craft::t('campaign', 'Create a new contact');
        }
        else {
            $variables['title'] = $contact->email;
        }

        // Add fields from first field layout tab
        $fieldLayoutTabs = $contact->getFieldLayout()->getTabs();
        $fieldLayoutTab = isset($fieldLayoutTabs[0]) ? $fieldLayoutTabs[0] : null;
        $variables['fields'] = $fieldLayoutTab !== null ? $fieldLayoutTab->getFields() : [];

        // Determine which actions should be available
        // ---------------------------------------------------------------------

        $variables['actions'] = [];

        // Add complain and bounce actions
        if ($contact->complained === null) {
            $variables['actions'][0][] = [
                'action' => 'campaign/contacts/mark-contact-complained',
                'redirect' => 'campaign/contacts/{id}',
                'label' => Craft::t('campaign', 'Mark contact as complained…'),
                'confirm' => Craft::t('campaign', 'Are you sure you want to mark this contact as complained?')
            ];
        }
        else {
            $variables['actions'][0][] = [
                'action' => 'campaign/contacts/unmark-contact-complained',
                'redirect' => 'campaign/contacts/{id}',
                'label' => Craft::t('campaign', 'Unmark contact as complained…'),
                'confirm' => Craft::t('campaign', 'Are you sure you want to unmark this contact as complained?')
            ];
        }
        if ($contact->bounced === null) {
            $variables['actions'][0][] = [
                'action' => 'campaign/contacts/mark-contact-bounced',
                'redirect' => 'campaign/contacts/{id}',
                'label' => Craft::t('campaign', 'Mark contact as bounced…'),
                'confirm' => Craft::t('campaign', 'Are you sure you want to mark this contact as bounced?')
            ];
        }
        else {
            $variables['actions'][0][] = [
                'action' => 'campaign/contacts/unmark-contact-bounced',
                'redirect' => 'campaign/contacts/{id}',
                'label' => Craft::t('campaign', 'Unmark contact as bounced…'),
                'confirm' => Craft::t('campaign', 'Are you sure you want to unmark this contact as bounced?')
            ];
        }

        $variables['actions'][1][] = [
            'action' => 'campaign/contacts/delete-contact',
            'destructive' => 'true',
            'redirect' => 'campaign/contacts',
            'label' => Craft::t('campaign', 'Delete…'),
            'confirm' => Craft::t('campaign', 'Are you sure you want to delete this contact?')
        ];

        // Get the settings
        $variables['settings'] = Campaign::$plugin->getSettings();

        // Full page form variables
        $variables['fullPageForm'] = true;
        $variables['continueEditingUrl'] = 'campaign/contacts/{id}';
        $variables['saveShortcutRedirect'] = $variables['continueEditingUrl'];

        // Render the template
        return $this->renderTemplate('campaign/contacts/_edit', $variables);
    }

    /**
     * @return Response|null
     * @throws NotFoundHttpException
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws BadRequestHttpException
     */
    public function actionSaveContact()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $contactId = $request->getBodyParam('contactId');

        if ($contactId) {
            $contact = Campaign::$plugin->contacts->getContactById($contactId);

            if ($contact === null) {
                throw new NotFoundHttpException(Craft::t('campaign', 'Contact not found.'));
            }
        }
        else {
            $contact = new ContactElement();
        }

        // Set the attributes, defaulting to the existing values for whatever is missing from the post data
        $contact->email = $request->getBodyParam('email', $contact->email);

        // Set the field layout ID
        $contact->fieldLayoutId = Campaign::$plugin->getSettings()->contactFieldLayoutId;

        // Set the field values using the fields location
        $fieldsLocation = $request->getParam('fieldsLocation', 'fields');
        $contact->setFieldValuesFromRequest($fieldsLocation);

        // Save it
        if (!Craft::$app->getElements()->saveElement($contact)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $contact->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t save contact.'));

            // Send the contact back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'contact' => $contact
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            $return = [];

            $return['success'] = true;
            $return['id'] = $contact->id;
            $return['email'] = $contact->email;

            if (!$request->getIsConsoleRequest() AND $request->getIsCpRequest()) {
                $return['cpEditUrl'] = $contact->getCpEditUrl();
            }

            $return['dateCreated'] = DateTimeHelper::toIso8601($contact->dateCreated);
            $return['dateUpdated'] = DateTimeHelper::toIso8601($contact->dateUpdated);

            return $this->asJson($return);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Contact saved.'));

        return $this->redirectToPostedUrl($contact);
    }

    /**
     * Marks a contact as complained
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    public function actionMarkContactComplained()
    {
        return $this->_markContactStatus('complained');
    }

    /**
     * Marks a contact as bounced
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    public function actionMarkContactBounced()
    {
        return $this->_markContactStatus('bounced');
    }

    /**
     * Unmarks a contact as complained
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    public function actionUnmarkContactComplained()
    {
        return $this->_unmarkContactStatus('complained');
    }

    /**
     * Unmarks a contact as bounced
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    public function actionUnmarkContactBounced()
    {
        return $this->_unmarkContactStatus('bounced');
    }

    /**
     * Deletes a contact
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    public function actionDeleteContact()
    {
        $this->requirePostRequest();

        $contact = $this->_getPostedContact();

        if (!Craft::$app->getElements()->deleteElement($contact)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t delete contact.'));

            // Send the contact back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'contact' => $contact
            ]);

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Contact deleted.'));

        return $this->redirectToPostedUrl($contact);
    }

    /**
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws Exception
     * @throws Throwable
     */
    public function actionSubscribeMailingList()
    {
        return $this->_updateSubscription('subscribed');
    }

    /**
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws Exception
     * @throws Throwable
     */
    public function actionUnsubscribeMailingList()
    {
        return $this->_updateSubscription('unsubscribed');
    }

    /**
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws Exception
     * @throws Throwable
     */
    public function actionRemoveMailingList()
    {
        return $this->_updateSubscription('');
    }

    // Private Methods
    // =========================================================================

    /**
     * @return ContactElement
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     */
    private function _getPostedContact(): ContactElement
    {
        $contactId = Craft::$app->getRequest()->getRequiredBodyParam('contactId');
        $contact = Campaign::$plugin->contacts->getContactById($contactId);

        if ($contact === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Contact not found.'));
        }

        return $contact;
    }

    /**
     * @param string $subscriptionStatus
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException if the requested contact or mailing list cannot be found
     * @throws Exception
     * @throws Throwable
     */
    private function _updateSubscription(string $subscriptionStatus)
    {
        $this->requirePostRequest();

        $contact = $this->_getPostedContact();

        $mailingListId = Craft::$app->getRequest()->getRequiredBodyParam('mailingListId');
        $mailingList = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);

        if ($mailingList === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Mailing list not found.'));
        }

        if ($subscriptionStatus === '') {
            Campaign::$plugin->mailingLists->deleteContactSubscription($contact, $mailingList);
        } else {
            Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, $subscriptionStatus, 'user', Craft::$app->getUser()->getIdentity()->id);
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'subscriptionStatus' => $subscriptionStatus,
                'subscriptionStatusLabel' => Craft::t('campaign', $subscriptionStatus ?: 'none'),
            ]);
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Marks a contact status
     *
     * @param string $status
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    private function _markContactStatus(string $status)
    {
        $this->requirePostRequest();

        $contact = $this->_getPostedContact();

        $contact->{$status} = new DateTime();

        if (!Craft::$app->getElements()->saveElement($contact)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t mark contact as {status}.', ['status' => $status]));

            // Send the contact back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'contact' => $contact
            ]);

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Contact marked as {status}.', ['status' => $status]));

        return $this->redirectToPostedUrl($contact);
    }

    /**
     * Unmarks a contact status
     *
     * @param string $status
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    private function _unmarkContactStatus(string $status)
    {
        $this->requirePostRequest();

        $contact = $this->_getPostedContact();

        $contact->{$status} = null;

        if (!Craft::$app->getElements()->saveElement($contact)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t unmark contact as {status}.', ['status' => $status]));

            // Send the contact back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'contact' => $contact
            ]);

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Contact unmarked as {status}.', ['status' => $status]));

        return $this->redirectToPostedUrl($contact);
    }
}
