#include <windows.h>
#include <iostream>
#include <stdexcept>
#include <chrono>
#include <thread>

ULONGLONG FileTimeToULL(const FILETIME& ft) {
    ULARGE_INTEGER uli;
    uli.LowPart = ft.dwLowDateTime;
    uli.HighPart = ft.dwHighDateTime;
    return uli.QuadPart;
}

double CalculateCPUUsage(const FILETIME& ftSysKernel1, const FILETIME& ftSysUser1, const FILETIME& ftSysKernel2, const FILETIME& ftSysUser2, const FILETIME& ftProcKernel1, const FILETIME& ftProcUser1, const FILETIME& ftProcKernel2, const FILETIME& ftProcUser2) {

    ULONGLONG sysKernel1 = FileTimeToULL(ftSysKernel1);
    ULONGLONG sysUser1 = FileTimeToULL(ftSysUser1);
    ULONGLONG sysKernel2 = FileTimeToULL(ftSysKernel2);
    ULONGLONG sysUser2 = FileTimeToULL(ftSysUser2);

    ULONGLONG procKernel1 = FileTimeToULL(ftProcKernel1);
    ULONGLONG procUser1 = FileTimeToULL(ftProcUser1);
    ULONGLONG procKernel2 = FileTimeToULL(ftProcKernel2);
    ULONGLONG procUser2 = FileTimeToULL(ftProcUser2);

    ULONGLONG sysTotal1 = sysKernel1 + sysUser1;
    ULONGLONG sysTotal2 = sysKernel2 + sysUser2;

    ULONGLONG procTotal1 = procKernel1 + procUser1;
    ULONGLONG procTotal2 = procKernel2 + procUser2;

    ULONGLONG sysDelta = sysTotal2 - sysTotal1;
    ULONGLONG procDelta = procTotal2 - procTotal1;

    if (sysDelta == 0) {
        return 0.0;
    }

    return (static_cast<double>(procDelta) / sysDelta) * 100;
}

int main(int argc, char* argv[]) {
    if (argc != 2) {
        std::cerr << "Usage: ProcessCPUUsage <PID>" << std::endl;
    exit(EXIT_FAILURE);
    }

    DWORD pid = std::stoul(argv[1], nullptr, 10);
    HANDLE hProcess = OpenProcess(PROCESS_QUERY_INFORMATION | PROCESS_VM_READ, FALSE, pid);
    if (!hProcess) {
        DWORD error = GetLastError();
        std::cerr << "Failed to open process. Error: " << error << std::endl;

        if (error == ERROR_ACCESS_DENIED) {
            std::cerr << "Access denied. Try running the program as an administrator." << std::endl;
        }

        return EXIT_FAILURE;
    }

    FILETIME ftSysIdle1, ftSysKernel1, ftSysUser1;
    FILETIME ftSysIdle2, ftSysKernel2, ftSysUser2;
    FILETIME ftProcCreation1, ftProcExit1, ftProcKernel1, ftProcUser1;
    FILETIME ftProcCreation2, ftProcExit2, ftProcKernel2, ftProcUser2;

    if (!GetSystemTimes(&ftSysIdle1, &ftSysKernel1, &ftSysUser1) ||
        !GetProcessTimes(hProcess, &ftProcCreation1, &ftProcExit1, &ftProcKernel1, &ftProcUser1)) {
        std::cerr << "Failed to get initial times. Error: " << GetLastError() << std::endl;
        CloseHandle(hProcess);
        return EXIT_FAILURE;
    }

    // Wait
    std::this_thread::sleep_for(std::chrono::milliseconds(100));

    if (!GetSystemTimes(&ftSysIdle2, &ftSysKernel2, &ftSysUser2) ||
        !GetProcessTimes(hProcess, &ftProcCreation2, &ftProcExit2, &ftProcKernel2, &ftProcUser2)) {
        std::cerr << "Failed to get subsequent times. Error: " << GetLastError() << std::endl;
        CloseHandle(hProcess);
        return EXIT_FAILURE;
    }

    double cpuUsage = CalculateCPUUsage(ftSysKernel1, ftSysUser1,
                                        ftSysKernel2, ftSysUser2,
                                        ftProcKernel1, ftProcUser1,
                                        ftProcKernel2, ftProcUser2);

    std::cout << cpuUsage;

    CloseHandle(hProcess);
    return 0;
}