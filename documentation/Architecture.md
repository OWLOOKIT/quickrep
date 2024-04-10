QuickRep Reporting Engine Overview
========

An advanced PHP reporting engine designed for optimal performance with Laravel, developed by [Owlookit Systems](https://owlookit.com)


Architecture Overview
------------------

```mermaid
graph LR
    subgraph database[" "]
        PostgreSQL_DB(PostgreSQL DB) -->|Read/Write| cache_db[_cache database]
    end

    subgraph backend["Backend Reporting Engine"]
        Report_Files(Report Files) --> backend_engine{Backend Reporting Engine}
        cache_db --> backend_engine
    end

    subgraph frontend["Frontend Report Viewers"]
        backend_engine -->|Based on datatables| Frontend_Report_Viewers
    end

    Report_Files -.->|Contains SQL Queries| backend_engine
    backend_engine -.->|Generate reports| Frontend_Report_Viewers

    style database fill:#f9f,stroke:#333,stroke-width:4px
    style backend fill:#bbf,stroke:#f66,stroke-width:2px,padding:10px
    style frontend fill:#bfb,stroke:#f66,stroke-width:2px,padding:10px
```

At its core, Quickrep operates by executing your SQL queries against your dataset and then transferring the results into a cache table (typically within the \_quickrep database). It then performs paging and sorting operations on this cached dataset.  
