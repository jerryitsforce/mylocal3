# Branch8_React Module

## Setup

### Install dependencies

Within the root of the module (app/code/Branch8/React) run the following command to install dependencies:

```
npm install
```

### Local Development

To start the local development server run the following command:

```
npm start
```

To build and use liveload to see the changes in development mode run the following command:

```
npm run build:dev
```

`NOTE: `
- need to refresh the magento page to see the changes

### Production

To build the production version of the module run the following command:

```
npm run build
```

## Environment Variables

```
NONE
```

## Usage

### Add a new feature 

Assume that I'm staying in the root of the module (app/code/Branch8/React)

1. Create a component as a wrapper of this feature in the `src/` directory: `src/Components/<Feature>.tsx`.

2. Create all child components of this feauture and its styles in the `src/components/<Feature>/` directory.

3. Edit webpack.common.js to add the new file at step 1. to the entry point.

```
 entry: {
    <feature>: "./src/<Feature>.tsx",
 },
```

after building, it will generate a new <feature>.js file in the `view/base/web/js` directory.

4. Include the generated js file in the template file of the page that you want to add the new feature.

To test the new feature, you can also add it to the `index.html` file in the `public/` directory.

Here is an example (avaiable in module): 
1. src/CustomerProfile.tsx
2. src/components/CustomerProfile/
3. webpack.common.js
```
  entry: {
    profile: "./src/CustomerProfile.tsx",
  },
```
4. 
- use in magento template file: 
`app/code/Branch8/React/view/frontend/templates/profile.phtml`

```php
<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/** @var $block \Magento\Framework\View\Element\Template */
?>
<div class="profile-section" id="profile-section"></div>
<script>
require([
    "Branch8_React/js/profile"
  ],
  function () {
    window.CPProfile({ data: {
      name: "John Doe",
      age: 30,
    }, elCls: "profile-section" });
  });
</script>

```
NOTE: you can run `npm run build:dev` to automatically rebuild when have the changes, but you need to refresh the magento page to see the changes

- use in public/index.html file: 

```html
<div class="profile-section" id="profile-section"></div>
<script type="text/javascript">
  setTimeout(async () => {
    while (typeof window.CPProfile !== 'function') {
      console.log('waiting for window.CPProfile to be available');
      await new Promise((resolve) => setTimeout(resolve, 500));
    }
    window.CPProfile({ data: {
      name: "John Doe",
      age: 30,
    }, elCls: "profile-section" });
  }, 1000);
</script>
```
NOTE: public/index.html is only for testing purpose, it will work when you run `npm run start`

## Styling

- [Styled-Components](https://styled-components.com/)

## Tech Stack

- React
- React Dom
- TypeScript
- Styled-Components
- ESLint
- Prettier
- Jest
- Webpack
