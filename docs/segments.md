# Segments

Segments (pro feature) are sets of conditions that filter contacts by specific fields, operators and values. They can contain an unlimited amount of AND and OR conditions, and can be applied to sendouts.

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