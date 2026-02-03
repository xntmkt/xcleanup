# Main Data Flow Diagram (DFD)

```
[Config] --> [CleanupCommand] --> [CleanupPlanner] --> [CleanupExecutor] --> [Reports]
                      |                          |                 |
                      v                          v                 v
               [DiskUsageReader]          [FilesystemScanner]  [NotifierComposite]
```
