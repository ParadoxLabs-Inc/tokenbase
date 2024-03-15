---
name: Performance Issue
about: Create a report about a performance problem.
title: '[PERF]'
labels: 'perf'
assignees: ''
---

<!--
PLEASE HELP US PROCESS GITHUB ISSUES FASTER BY PROVIDING THE FOLLOWING INFORMATION.

ISSUES MISSING IMPORTANT INFORMATION MAY BE CLOSED WITHOUT INVESTIGATION.
-->

# :turtle: Performance Issue

## Current behavior
<!-- Describe how the issue manifests. -->


## Expected behavior
<!-- Describe what the expected behavior is. -->


## Minimal reproduction of the problem with instructions
<!-- Please provide the *STEPS TO REPRODUCE* and if possible a *MINIMAL DEMO* of the problem -->


## What is the motivation / use case for changing the behavior?
<!-- Describe the motivation or the concrete use case. -->


## Environment

<!-- 
```bash
echo "
TokenBase version: $(composer show paradoxlabs/tokenbase  | sed -n '/versions/s/^[^0-9]\+\([^,]\+\).*$/\1/p')
Magento version: $(composer show paradoxlabs/tokenbase  | sed -n '/versions/s/^[^0-9]\+\([^,]\+\).*$/\1/p')
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