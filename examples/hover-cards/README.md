# Hover-Style Event Cards

This example shows how one could use the data to generate a template that contains custom created card-style event displays. The event cards, when hovered (or focused) will display additional information about the event, as provided from the event title, description, and whether the event requires registration or not. A button-like link is also shown to prompt the visitor to click on it for more information, but the whole image is linked, and will direct the visitor to the full event detail page as hosted on LibCal.

![hover-card-github](hover-card-github.gif)

> [!NOTE]
> The truncated string for the event description in this template was implemented quickly as an example: any HTML elements that don't have an ending tag due to where the text was truncated may cause overflow of that HTML tag's effect (ex: bold, italic, etc.). If this were to be used in production, I'd suggest using something like [HTMLPurifier](http://htmlpurifier.org/) on the description. It was not included in this example to limit any external dependencies.