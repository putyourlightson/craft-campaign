# Segments

Segments (pro feature) are sets of conditions that filter contacts by specific fields, operators and values. They can contain an unlimited amount of AND and OR conditions, and can be applied to sendouts.

### Segment Types

The types of conditions available to segments are determined by the segment type they belong to.

#### Regular
With regular segments, conditions can be combined by logical AND and OR operators in order to create a very unique segmentation of contacts. Each supported contact field offers a different operator and value format, for example plaintext fields allow string comparisons while date fields allow date comparisons.

![Segment Conditions](https://raw.githubusercontent.com/putyourlightson/craft-campaign/v1/docs/images/segment-conditions-1.9.0.png)  

#### Template
Template segments provide a textarea in which you can add as much twig template code as you want. The template should output a string that evaluates to `true` (anything except for 0 or a blank string). The `contact` tag is available in this context.

    {{ contact.dateOfBirth|date('Y') == 1980 ? 1 : 0 }}

> Note that template conditions require processing template code for every contact and can therefore slow down the sending process. Use them sparingly and only when a regular segment is insufficient.

![Segment Conditions](https://raw.githubusercontent.com/putyourlightson/craft-campaign/v1/docs/images/segment-template-1.9.0.png)  

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
