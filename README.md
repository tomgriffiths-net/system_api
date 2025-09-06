# system_api
This is a package for PHP-CLI

# Functions
- **getProcessCpuUsage(string|int $processId):float**: Gets a processes CPU usage, returns 0 on failure.
- **getProcessMemoryUsage(string|int $pid):int**: Gets a processes Memory usage in MB, returns 0 on failure.
- **getProcessChildProcesses(string|int $parentPid):array**: Gets a list of child processes pids, returns an empty array on failure.
- **getOpenWindows():array**: Gets the open windows in the current desktop session, returns an empty array on failure.
- **isVisualRedistributableInstalled(string $version="14.0"):bool**: Checks weather Visual C++ Redistributables are installed, returns true on success or false on failure.