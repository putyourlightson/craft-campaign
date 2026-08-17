<?php

namespace putyourlightson\campaign\mail;

use Craft;
use craft\enums\CmsEdition;
use craft\helpers\App;
use craft\helpers\Markdown;
use craft\helpers\Template;
use craft\mail\Mailer;
use craft\mail\Message;
use craft\web\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;
use yii\mail\BaseMailer;
use yii\mail\MailEvent;

class CampaignMailer extends Mailer
{
    /**
     * @var TransportExceptionInterface|null The last exception that occurred during sending.
     */
    public ?TransportExceptionInterface $lastException = null;

    /**
     * A direct copy of [[\craft\mail\Mailer::send()]] with the addition of storing the last exception that occurred during sending.
     */
    public function send($message): bool
    {
        // Fire a 'beforePrep' event
        $this->trigger(self::EVENT_BEFORE_PREP, new MailEvent([
            'message' => $message,
        ]));

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $sitesService = Craft::$app->getSites();
        $view = Craft::$app->getView();
        $currentSite = $messageSite = $twig = null;
        $language = Craft::$app->language;
        $generateTransformsBeforePageLoad = $generalConfig->generateTransformsBeforePageLoad;
        $originalSettings = [];

        $originalTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

        try {
            if ($message instanceof Message && isset($message->siteId)) {
                $currentSite = $sitesService->getCurrentSite();
                if ($message->siteId !== $currentSite->id) {
                    $messageSite = $sitesService->getSiteById($message->siteId);
                    if ($messageSite) {
                        $sitesService->setCurrentSite($messageSite);
                        // reset Twig so any global sets and singles get reloaded for the new site
                        $twig = $view->getTwig();
                        $view->setTwig($view->createTwig());
                    }
                }
            }

            $overrides = $this->siteOverrides[$sitesService->getCurrentSite()->uid] ?? [];
            if (isset($overrides['fromEmail']) || isset($overrides['fromName'])) {
                $originalSettings['from'] = $this->from;
                $fromEmail = $overrides['fromEmail'] ?? array_key_first($this->from);
                $fromName = $overrides['fromName'] ?? reset($this->from);
                /** @phpstan-ignore-next-line */
                $this->from = [
                    App::parseEnv($fromEmail) => App::parseEnv($fromName),
                ];
            }
            if (isset($overrides['replyToEmail'])) {
                $originalSettings['replyTo'] = $this->replyTo;
                $this->replyTo = App::parseEnv($overrides['replyToEmail']);
            }
            if (isset($overrides['template'])) {
                $originalSettings['template'] = $this->template;
                $this->template = App::parseEnv($overrides['template']);
            }

            if ($message instanceof Message && $message->key !== null) {
                if ($message->language === null) {
                    // If a site was specified, go with its language
                    if ($messageSite) {
                        $message->language = $messageSite->language;
                    } else {
                        // Default to the current language
                        $message->language = Craft::$app->getRequest()->getIsSiteRequest()
                            ? Craft::$app->language
                            : Craft::$app->getSites()->getPrimarySite()->language;
                    }
                }

                // Use the message language
                Craft::$app->language = $message->language;

                // Temporarily disable lazy transform generation
                $generalConfig->generateTransformsBeforePageLoad = true;

                $systemMessage = Craft::$app->getSystemMessages()->getMessage($message->key, $message->language);

                $settings = App::mailSettings();
                $variables = ($message->variables ?: []) + [
                        'emailKey' => $message->key,
                        'fromEmail' => App::parseEnv($settings->fromEmail),
                        'replyToEmail' => App::parseEnv($settings->replyToEmail),
                        'fromName' => App::parseEnv($settings->fromName),
                        'language' => $message->language,
                    ];

                // Render the subject and body text
                $subject = $view->renderSandboxedString($systemMessage->subject, $variables);
                $textBody = $view->renderSandboxedString($systemMessage->body, $variables);
                $htmlBody = $view->renderSandboxedString($systemMessage->body, $variables, escapeHtml: true);

                // Remove </> from around URLs, so they’re not interpreted as HTML tags
                $textBody = preg_replace('/<(https?:\/\/.+?)>/', '$1', $textBody);

                $message->setSubject($subject);
                $message->setTextBody($textBody);

                // Is there a custom HTML template set?
                if (Craft::$app->edition->value >= CmsEdition::Pro->value && $this->template) {
                    $template = $this->template;
                    $templateMode = View::TEMPLATE_MODE_SITE;
                } else {
                    // Default to the _special/email.html template
                    $template = '_special/email.twig';
                    $templateMode = View::TEMPLATE_MODE_CP;
                }

                try {
                    $message->setHtmlBody($view->renderTemplate($template, array_merge($variables, [
                        'body' => Template::raw(Markdown::process($htmlBody, 'gfm-comment')),
                    ]), $templateMode));
                } catch (Throwable $e) {
                    // Just log it and don't worry about the HTML body
                    Craft::warning('Error rendering email template: ' . $e->getMessage(), __METHOD__);
                    Craft::$app->getErrorHandler()->logException($e);
                }
            }

            // Set the default sender if there isn't one already
            if (!$message->getFrom()) {
                $message->setFrom($this->from);
            }

            if ($this->replyTo && !$message->getReplyTo()) {
                $message->setReplyTo($this->replyTo);
            }

            // Apply the testToEmailAddress config setting
            $testToEmailAddress = $generalConfig->getTestToEmailAddress();
            if (!empty($testToEmailAddress)) {
                $message->setTo($testToEmailAddress);
                $message->setCc([]);
                $message->setBcc([]);
            }

            // Call the base mailer (not the Craft mailer, which catches exceptions).
            return BaseMailer::send($message);
        } catch (TransportExceptionInterface $e) {
            Craft::warning('Error sending email: ' . $e->getMessage(), __METHOD__);
            Craft::$app->getErrorHandler()->logException($e);

            // Store the last exception so we can reference it later.
            $this->lastException = $e;

            return false;
        } finally {
            // Set things back to normal
            Craft::$app->language = $language;
            $generalConfig->generateTransformsBeforePageLoad = $generateTransformsBeforePageLoad;

            if ($currentSite && $messageSite) {
                $sitesService->setCurrentSite($currentSite);
            }

            $view->setTemplateMode($originalTemplateMode);

            if ($twig) {
                $view->setTwig($twig);
            }

            Craft::configure($this, $originalSettings);
        }
    }
}
