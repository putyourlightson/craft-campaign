# Segments

Segments (pro feature) are sets of conditions that filter contacts by specific fields, operators and values. They can contain an unlimited amount of AND and OR conditions, and can be applied to sendouts.

### Segment Conditions
Conditions can be combined by logical AND and OR operators in order to create a very unique segmentation of contacts. Each condition type offers a different value format, for example plaintext fields allow string comparisons while date fields allow date comparisons.

The "Template" condition type is extremely powerful, in that it allows you to specify a twig template in which you can add as much twig logic as you want. The template should output a string that either evaluates to `false` (0 or a blank string) or `true` (anything else).  
![Segment Conditions](https://raw.githubusercontent.com/putyourlightson/craft-campaign/develop/docs/images/segment-conditions-1.2.0.png)  

### Getting Segments
You can access segments from your templates with `craft.campaign.segments` which returns an [Element Query](https://docs.craftcms.com/v3/element-queries.html).

    // Gets the first segment with the specified ID
    {% set segment = craft.campaign.segments.id(3).one %}
    
    {% if segment %}
       Segment {{ segment.title }}
    {% endif %} 

You can get segments from your plugin with `SegmentElement::find()` which returns an [Element Query](https://docs.craftcms.com/v3/element-queries.html). 

    use putyourlightson\campaign\elements\SegmentElement;

    $segment = SegmentElement::find()->id(3)->one();

The returned Element Query supports the parameters that all element types in Craft support (`id`, `title`, etc.).