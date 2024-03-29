---
name: Documentation Request
about: Request additional documentation or clarification on a feature.
title: '[DOCS]'
labels: 'docs'
assignees: ''
---

<!--
PLEASE HELP US PROCESS GITHUB ISSUES FASTER BY PROVIDING THE FOLLOWING INFORMATION.

ISSUES MISSING IMPORTANT INFORMATION MAY BE CLOSED WITHOUT INVESTIGATION.
-->

# :page_facing_up: Documentation Request

## What were you doing?
<!-- Describe how you came to need the documentation. -->


## Expected behavior
<!-- Describe not only **what** you would like to see documented, but also **where** you'd like to see it. -->


## Existing Documentation
<!-- Describe any existing documentation that would potentially require change. -->

## Environment

<!-- 
```bash
echo "
TokenBase version: $(composer show paradoxlabs/tokenbase  | sed -n '/versions/s/^[^0-9]\+\([^,]\+\).*$/\1/p')
Magento version: $(composer show magento/product-community-edition  | sed -n '/versions/s/^[^0-9]\+\([^,]\+\).*$/\1/p')
PHP version: $(php --version)
"
```
-->

<pre><code>
TokenBase version: X.Y.Z 
Magento version: X.Y.Z
PHP version: X.Y.Z 
<!-- Check whether this is still an issue in the most recent TokenBase version -->

Others:
<!-- Anything else relevant?  Operating system version, IDE, package manager, HTTP server, ... -->
</code></pre>