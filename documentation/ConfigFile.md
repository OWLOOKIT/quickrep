# QuickRep Config File Documentation

Understanding the various configuration variables for QuickRep and their functions.

* `REPORT_NAMESPACE` - Defines the expected PHP namespace for the reports, which also dictates the directory for their storage. By default, reports are located in `app/Reports` and use the namespace `App\Reports`.
* `API_PREFIX` - Anticipating that you might have existing endpoints under the `/api` URL segment, QuickRep API calls are prefixed with `/qrapi` by default. This variable allows for customization.
* `TABULAR_API_PREFIX` - Identifies the location for both the web UX and API of the tabular reports. The default setting `'Quickrep'` means that `https://yoursite.com/Quickrep/YourReportTabularNameHere/` is ready to use immediately. Note that the Card reporting engine shares the same API as the tabular reports, but its UX URL is `https://yoursite.com/QuickrepCard/YourCardReportNameHere/`.
* `TREEAPI_PREFIX` - Points to the web UX and API for tree reports. By default, it's set to `'QuickrepTree'`, so `https://yoursite.com/QuickrepTree/YourTreeReportNameHere/` should function directly after setup.
* `GRAPH_API_PREFIX` - The default prefix for graph reports' UX and API is `'QuickrepGraph'`, enabling access via `https://yoursite.com/QuickrepGraph/YourGraphReportNameHere/`.
* `RESTRICT_TAGS` - Determines whether the system should enforce strict adherence to formatting tags.
* `MIDDLEWARE` - Here you can specify additional middleware for QuickRep to recognize, such as authentication layers.
* `TAGS` - When using `RESTRICT_TAGS`, these are the formatting tags that should be allowed.
* `QUICKREP_CACHE_DB` - Designates the database for QuickRep's caching mechanism.
* `QUICKREP_CONFIG_DB` - Indicates the database where QuickRep's configuration settings are stored.
