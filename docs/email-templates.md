# Email Templates

Email templates are defined in the campaign type's settings page. A HTML as well as a plaintext email template should be provided that exist in the site's `templates` folder. 

The following template tags are available to email templates:  
`campaign`, `browserVersionUrl`, `contact`, `unsubscribeUrl`

### Sample Code

The following sample code shows how the tags can be used. Checking for the existance of the tags will ensure that they are only output when not blank, as may be the case for test emails and web versions.

    {% if browserVersionUrl %}
      <a href="{{ browserVersionUrl }}">View this email in your browser</a>
    {% endif %}
    
    {% if contact.firstName %}
      Hello {{ contact.firstName }},
    {% endif %}
    
    Welcome to this month's newsletter in which we have some fascintaing announcements!
    
    {{ campaign.announcements }}
    
    {% if unsubscribeUrl %}
      <a href="{{ unsubscribeUrl }}">Unsubscribe from this mailing list</a>
    {% endif %}
    
### Designing Templates 

The majority of email clients either offer no support at all for CSS and floated elements or are inconsistent in how they display them, so email templates should be built using tables. Since designing, building and testing a reliable email template (that works in all email clients) can be a daunting, time-consuming task, we've collected some recommended resources that provide lots of useful information as well as some links to free tried-and-tested email templates that you can customise to your specific needs.

### Recommended
We highly recommend [MJML](https://mjml.io/), a markup language and framework for building responsive email templates. The free [MJML desktop app](https://mjmlio.github.io/mjml-app/) makes coding email templates quick and easy. Watch the video created by Philip Thygesen of [Boomy](https://www.boomy.co.uk/).

[![MJML Video](https://raw.githubusercontent.com/putyourlightson/craft-campaign/v1/docs/images/mjml-video-1.5.2.jpg)](https://drive.google.com/file/d/1WYG5-6RNB_5D8F_q6RoXH9gQZzcodTgp/view)

### Frameworks
Foundation for Emails 2 is a framework for building responsive email templates using CSS or SASS without having to code tables by hand.  
[https://foundation.zurb.com/emails.html](https://foundation.zurb.com/emails.html)  

### Guides
A step-by-step guide to creating email templates by EmailMonks.  
[https://emailmonks.com/blog/email-coding/step-step-guide-create-html-email/](https://emailmonks.com/blog/email-coding/step-step-guide-create-html-email/)

A comprehensive guide to coding email templates by Campaign Monitor.  
[https://www.campaignmonitor.com/dev-resources/guides/coding/](https://www.campaignmonitor.com/dev-resources/guides/coding/)

A complete guide to coding email template and email marketing in general by MailChimp (some things are specific to MailChimp).  
[https://templates.mailchimp.com/](https://templates.mailchimp.com/)

The "Ultimate Guide to
Email Optimization + Troubleshooting" by Litmus.  
[https://litmus.com/lp/email-optimization-tips](https://litmus.com/lp/email-optimization-tips)

### HTML Templates
Zurb offers an excellent range of free responsive HTML email templates that you can easily customise to match your site's design.  
[https://foundation.zurb.com/emails/email-templates.html](https://foundation.zurb.com/emails/email-templates.html)  
<img src="https://raw.githubusercontent.com/putyourlightson/craft-campaign/v1/docs/images/email-templates-zurb.png" />

Litmus offers a wide range of free HTML email templates that you can easily customise yourself or using the Litmus Builder.  
[https://litmus.com/community/templates](https://litmus.com/community/templates)  
<img src="https://raw.githubusercontent.com/putyourlightson/craft-campaign/v1/docs/images/email-templates-litmus.png" />

### Testing
A complete breakdown of the CSS support for the most popular mobile, web and desktop email clients.  
[https://www.campaignmonitor.com/css/](https://www.campaignmonitor.com/css/)

As well as offering many useful resources, Litmus allows you to design and test your email templates in all major email clients.  
[https://litmus.com/](https://litmus.com/)