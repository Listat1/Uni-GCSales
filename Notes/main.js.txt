The layout originally relied on JavaScript for positioning, but it now uses CSS sticky positioning to prevent key interface elements from scrolling out of view.

By using a sticky approach, elements are allowed to scroll naturally until they reach the top of the visible area. In this case, the visible area is the viewport minus a fixed header. Once an element reaches that boundary, it remains fixed in place while the rest of the content continues to scroll.

Each element that needs this behaviour is given its own class, allowing it to be positioned and managed independently. This makes it possible to stack multiple sticky elements vertically, with each one sticking just below the element above it.

If one of these elements is removed from the layout, the remaining elements automatically shift upward and occupy the next available sticky position. As a result, the highest-priority element always stays visible at the top of the screen, while lower-priority elements only consume space when they are present.

This approach keeps the interface flexible, avoids unnecessary JavaScript, and ensures important controls remain accessible without breaking the natural flow of the page.