# ICT311/ICT321 Capstone II Weekly Progress Tracking

## Week 6

### Tasks Completed

What did you achieve this week?

In week 6, we focused on adding the main security scanning tools into the backend.

We integrated Semgrep and Gitleaks for source code and secret scanning.

We also started to normalize the scan results into one common format so the system can store and display them properly.

We connected the scan results with the database and saved findings under each scan.

We tested the scanning flow with sample GitHub repositories to check if the output was working correctly.

### Individual Contributions

Prashant Poudel  
Worked on backend scanner integration, queue job flow, and saving scan results in the database.

Dipak Acharya  
Helped check how scan findings can be shown on the frontend and gave UI suggestions.

Sudip Gautam  
Researched security scanner output and helped improve the scan result format.

Sagar Dhakal  
Updated project documentation and supported testing and coordination.

### Challenges Faced

Any difficulties or roadblocks?

Different tools were giving different output formats.

Some scan results needed cleaning before saving in the database.

We had to make sure the scanner runs safely inside the project workspace.

### Next Steps

What will you focus on next?

We will focus on improving scan result processing.

We will start work on risk scoring and vulnerability grouping.

We will also begin AI-based remediation suggestions.

End of week 6

## Week 7

### Tasks Completed

What did you achieve this week?

In week 7, we continued the work from week 6 and focused on improving the scanning flow.

We worked on implementing the scanning tools Semgrep and Gitleaks more properly in the backend.

We improved the way scan results are processed and stored in the database.

We started to work on risk scoring and vulnerability grouping based on the scan results.

We also started planning AI-based remediation suggestions for the findings.

### Individual Contributions

Prashant Poudel  
Worked on backend scan processing and helped improve the scanning workflow.

Dipak Acharya  
Helped review how scan findings will be shown on the frontend.

Sudip Gautam  
Helped improve the scan result format and reviewed the vulnerability output.

Sagar Dhakal  
Helped with documentation and supported testing and coordination.

### Challenges Faced

Any difficulties or roadblocks?

Implementation of scanning tools.

Some scan results still needed better processing before scoring.

We had to make sure the output from different tools stayed consistent.

### Next Steps

What will you focus on next?

We will focus on risk scoring and vulnerability grouping.

We will continue improving Semgrep and Gitleaks integration.

We will also start AI-based remediation suggestions.

End of week 7

## Week 8

### Tasks Completed

What did you achieve this week?

In week 8, we worked on analytics and reporting features.

We developed trend analysis for scan history so users can see improvement or decline over time.

We started using the Python analytics module to process scan data.

We also worked on PDF report generation for completed scans.

We connected the report feature with the backend so users can download scan reports.

We improved the dashboard to show chart-based information for scan results and overall health.

### Individual Contributions

Prashant Poudel  
Integrated analytics and report generation with the backend.

Dipak Acharya  
Improved dashboard charts and report-related frontend pages.

Sudip Gautam  
Helped analyze scan trends and checked the security data shown in reports.

Sagar Dhakal  
Supported documentation, UI review, and project coordination.

### Challenges Faced

Any difficulties or roadblocks?

Trend analysis needed clean and consistent scan history.

PDF report formatting took time to make readable.

We had to connect analytics output with the database properly.

### Next Steps

What will you focus on next?

We will work on scheduled repository scans.

We will test the full end-to-end workflow from repository submission to report generation.

We will start final bug fixing, testing, and deployment preparation.

End of week 8

## Week 9

### Tasks Completed

What did you achieve this week?

In week 9, we focused on final testing and project completion.

We tested the full system flow from user login to repository submission, scanning, analytics, and report generation.

We fixed bugs found in the backend and frontend.

We improved the stability of the scheduled scan feature.

We checked if all modules were working properly in Docker.

We prepared the final version of the project for submission and presentation.

### Individual Contributions

Prashant Poudel  
Managed final backend fixes, deployment checks, and overall integration.

Dipak Acharya  
Finalized the frontend pages and improved the user interface.

Sudip Gautam  
Helped with security testing, result checking, and final scan validation.

Sagar Dhakal  
Completed documentation, project board updates, and presentation support.

### Challenges Faced

Any difficulties or roadblocks?

Some final bugs appeared during testing.

We had to make sure all services worked together without errors.

Deployment and environment setup needed final checks.

### Next Steps

What will you focus on next?

We will prepare the final presentation and demo.

We will submit the completed project documentation.

We will keep the system ready for final review and marking.

End of week 9
