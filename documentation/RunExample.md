# QuickRep Reporting Engine Running the Examples

An adept PHP reporting engine, finely tuned for Laravel, lovingly crafted by [Owlookit Systems](https://owlookit.com).

## Running the Example
To demonstrate Quickrep's capabilities, we utilize a modified version of the classic Northwind database, including both the schema and data to enable you to swiftly activate example reports...

The testing databases should be located in a directory adjacent to your Laravel project directory. The exact path will depend on your Laravel configuration.

Sample database tables and reports, based on the Northwind customer database, are provided in the `example` directory of the Quickrep project.

These testing databases are compatible with both flagship Owlookit projects: [DURC](https://github.com/Owlookit/DURC) and Quickrep (this project).

1. To start, clone the test databases repository and ensure they are properly loaded using your preferred database administration tool.

    ```
    $ git clone https://github.com/Owlookit/MyWind_Test_Data.git
    $ cd MyWind_Test_Data/
    $ php load_databases.php
    ```

   For installing the socket data for `NorthwindCustomerSocketReport.php`, use 'mysql source' with the data in `examples/`
    ```
    cd [project-root]
    mysql 
    use _quickrep_config;
    source vendor/owlookit/quickrep/examples/data/_quickrep_config.northwind_socket_example.sql;
    ```

2. Next, transfer the example reports from `[project-root]/vendor/owlookit/quickrep/examples/reports` into your `app/Reports` directory. If the `app/Reports` directory does not exist, you will need to create it first. This can be done from your project root:

    ```
    $ cp vendor/owlookit/quickrep/examples/reports/* app/Reports
    ```

You can access each example report through the Quickrep report URL. Assuming the default URLs in the Quickrep configuration remain unchanged, the reports can be loaded as follows:

- For tabular views of the Example Reports:
    ```
    [base_url]/Quickrep/NorthwindCustomerReport
    [base_url]/Quickrep/NorthwindOrderReport
    [base_url]/Quickrep/NorthwindProductReport
    ```
