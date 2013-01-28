/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#include <string>
using namespace std;

#ifdef WIN32
#include <stdlib.h>
#include <malloc.h>
#include <memory.h>
#include <winsock2.h>
#include "../trayicon/SystemTraySDK.h"
#include "../log/Log.h"
#include "AgentEngine.h"
#include "agentmain.h"

using namespace toast;
#define MAX_LOADSTRING 100
#define	WM_ICON_NOTIFY WM_APP+10
//HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Windows\CurrentVersion\Run
// Global Variables:
HINSTANCE hInst;								// current instance
TCHAR szTitle[MAX_LOADSTRING];					// The title bar text
TCHAR szWindowClass[MAX_LOADSTRING];			// The title bar text
CSystemTray g_TrayIcon;

// Foward declarations of functions included in this code module:
ATOM				MyRegisterClass(HINSTANCE hInstance);
BOOL				InitInstance(HINSTANCE, int);
LRESULT CALLBACK	WndProc(HWND, UINT, WPARAM, LPARAM);
LRESULT CALLBACK	About(HWND, UINT, WPARAM, LPARAM);
void Stop()
{
	AgentEngine::instance()->stop();
	WSACleanup();
}
int RetriveInstallPath(char *buf, int *buf_length)
{
	HKEY hkey;
	char szKey[1024];
	DWORD keyType;
	strcpy(szKey, "Software\\Etao\\ToastAgent\\");
	if (RegOpenKeyEx(HKEY_LOCAL_MACHINE,
		szKey,
		0,
		KEY_QUERY_VALUE,
		&hkey) == ERROR_SUCCESS) {
			// read the path
			if(RegQueryValueEx(hkey, "Path", 0, &keyType, (LPBYTE)buf, (LPDWORD)buf_length)== ERROR_SUCCESS)
			{
				buf[*buf_length] = '\0';
			    RegCloseKey(hkey);
				return ERROR_SUCCESS;
			}
		RegCloseKey(hkey);
	}
	return GetLastError();
}

int Init()
{
	WSADATA wsaData;
	char szInstallPath[2048]="";
	char szLogName[2048]="";
	int pathLength = 2048;
	if(RetriveInstallPath(szInstallPath, &pathLength))
        return 1;
	strcpy(szLogName, szInstallPath);
	strcat(szInstallPath, "\AgentDaemon.conf");
	InitConfigFile(szInstallPath);
	strcat(szLogName, "AgentDaemon.log");
	SimpleConfig::Instance()->SetDefaultValue("LOG", "log4cpp.appender.MAIN.fileName", szLogName);
	if(Log::Init(szInstallPath))
        return 1;
	Log::set_prog_name("toastagent");
	Log::set_mod_name("toastagent");
	char szBuf[255];
    Log::Info("Log init end");
	int retVal = WSAStartup(MAKEWORD(2,2), &wsaData);
	if (retVal != 0)
	{
		Log::Error("WSAStartup failed. Error = %d\n", retVal);
		return 1;        
	}
	//set global string value from config file 
	Log::Info("AgentEngine::instance()->run()");
	AgentEngine::instance()->run();
	return 0;
}

int APIENTRY WinMain(HINSTANCE hInstance,
	HINSTANCE hPrevInstance,
	LPSTR     lpCmdLine,
	int       nCmdShow)
{
    // make sure there is only one agent running!
    HANDLE hEvent =  OpenEvent(EVENT_ALL_ACCESS, FALSE, "ToastAgentMutex");
    if(hEvent==NULL)
    {
     hEvent = CreateEvent(NULL, TRUE, FALSE, TEXT("ToastAgentMutex")); 
    }
    else
    {
        MessageBox(NULL, "There is toastagent run, please stop it and run again", "Alert!", MB_OK);
        return 1;
    }
	MSG msg;
	HACCEL hAccelTable;

	// Initialize global strings
	LoadString(hInstance, IDS_APP_TITLE, szTitle, MAX_LOADSTRING);
	LoadString(hInstance, IDC_TASKBARDEMO, szWindowClass, MAX_LOADSTRING);
	MyRegisterClass(hInstance);

	// Perform application initialization:
	if (!InitInstance (hInstance, SW_HIDE)) 
	{
		return FALSE;
	}

	hAccelTable = LoadAccelerators(hInstance, (LPCTSTR)IDC_TASKBARDEMO);
	if(Init())
      {
        MessageBox(NULL, "Toastagent init error!", "Alert!", MB_OK);
        return 1;
       }
	// Main message loop:
	while (GetMessage(&msg, NULL, 0, 0)) 
	{
		if (!TranslateAccelerator(msg.hwnd, hAccelTable, &msg)) 
		{
			TranslateMessage(&msg);
			DispatchMessage(&msg);
		}
	}

	return msg.wParam;
}



//
//  FUNCTION: MyRegisterClass()
//
//  PURPOSE: Registers the window class.
//
//  COMMENTS:
//
//    This function and its usage is only necessary if you want this code
//    to be compatible with Win32 systems prior to the 'RegisterClassEx'
//    function that was added to Windows 95. It is important to call this function
//    so that the application will get 'well formed' small icons associated
//    with it.
//
ATOM MyRegisterClass(HINSTANCE hInstance)
{
	WNDCLASSEX wcex;

	wcex.cbSize = sizeof(WNDCLASSEX); 

	wcex.style			= CS_HREDRAW | CS_VREDRAW;
	wcex.lpfnWndProc	= (WNDPROC)WndProc;
	wcex.cbClsExtra		= 0;
	wcex.cbWndExtra		= 0;
	wcex.hInstance		= hInstance;
	wcex.hIcon			= LoadIcon(hInstance, MAKEINTRESOURCE(IDI_ICONRED));
	wcex.hCursor		= LoadCursor(NULL, IDC_ARROW);
	wcex.hbrBackground	= (HBRUSH)(COLOR_WINDOW+1);
	wcex.lpszMenuName	= (LPCSTR)IDC_TASKBARDEMO;
	wcex.lpszClassName	= szWindowClass;
	wcex.hIconSm		= LoadIcon(wcex.hInstance, MAKEINTRESOURCE(IDI_ICONRED));

	return RegisterClassEx(&wcex);
}

//
//   FUNCTION: InitInstance(HANDLE, int)
//
//   PURPOSE: Saves instance handle and creates main window
//
//   COMMENTS:
//
//        In this function, we save the instance handle in a global variable and
//        create and display the main program window.
//
BOOL InitInstance(HINSTANCE hInstance, int nCmdShow)
{
	HWND hWnd;

	hInst = hInstance; // Store instance handle in our global variable

	hWnd = CreateWindow(szWindowClass, szTitle, WS_OVERLAPPEDWINDOW,
		CW_USEDEFAULT, 0, CW_USEDEFAULT, 0, NULL, NULL, hInstance, NULL);

	if (!hWnd)
	{
		return FALSE;
	}

	// Create the tray icon
	if (!g_TrayIcon.Create(hInstance,
		hWnd,                            // Parent window
		WM_ICON_NOTIFY,                  // Icon notify message to use
		_T("Toastagent disconnected!"),  // tooltip
		::LoadIcon(hInstance, MAKEINTRESOURCE(IDI_ICONRED)),
		IDR_POPUP_MENU)) 
		return FALSE;

	ShowWindow(hWnd, nCmdShow);
	UpdateWindow(hWnd);

	return TRUE;
}

//
//  FUNCTION: WndProc(HWND, unsigned, WORD, LONG)
//
//  PURPOSE:  Processes messages for the main window.
//
//  WM_COMMAND	- process the application menu
//  WM_PAINT	- Paint the main window
//  WM_DESTROY	- post a quit message and return
//
//
LRESULT CALLBACK WndProc(HWND hWnd, UINT message, WPARAM wParam, LPARAM lParam)
{
	int wmId, wmEvent;
	PAINTSTRUCT ps;
	HDC hdc;
	TCHAR szHello[MAX_LOADSTRING];
	LoadString(hInst, IDS_HELLO, szHello, MAX_LOADSTRING);

	switch (message) 
	{
	case WM_ICON_NOTIFY:
		return g_TrayIcon.OnTrayNotification(wParam, lParam);

	case WM_COMMAND:
		wmId    = LOWORD(wParam); 
		wmEvent = HIWORD(wParam); 
		// Parse the menu selections:
		switch (wmId)
		{
		case IDM_ANIMATE:
			g_TrayIcon.SetIconList(IDI_ICON1, IDI_ICON4);
			g_TrayIcon.Animate(50, 2);  // 50 millisecond delay between frames, for 2 secs
			break; 
		case IDM_ABOUT:
			DialogBox(hInst, (LPCTSTR)IDD_ABOUTBOX, hWnd, (DLGPROC)About);
			break;
		case IDM_EXIT:
			DestroyWindow(hWnd);
			break;
		default:
			return DefWindowProc(hWnd, message, wParam, lParam);
		}
		break;
	case WM_PAINT:
		hdc = BeginPaint(hWnd, &ps);
		RECT rt;
		GetClientRect(hWnd, &rt);
		DrawText(hdc, szHello, strlen(szHello), &rt, DT_VCENTER | DT_CENTER | DT_SINGLELINE |DT_WORDBREAK);
		EndPaint(hWnd, &ps);
		break;

	case WM_DESTROY:
		Stop();
		PostQuitMessage(0);
		break;
	default:
		return DefWindowProc(hWnd, message, wParam, lParam);
	}
	return 0;
}

// Mesage handler for about box.
LRESULT CALLBACK About(HWND hDlg, UINT message, WPARAM wParam, LPARAM lParam)
{
	switch (message)
	{
	case WM_INITDIALOG:
		return TRUE;

	case WM_COMMAND:
		if (LOWORD(wParam) == IDOK || LOWORD(wParam) == IDCANCEL) 
		{
			EndDialog(hDlg, LOWORD(wParam));
			return TRUE;
		}
		break;
	}
	return FALSE;
}
#else
#include "AgentEngine.h"
#include "../daemon/Daemon.h"
#include "../log/Log.h"
#include <locale.h>
#include <signal.h>
using namespace toast;

int main(int argc,char* argv[])
{
	//Ignore the SIGPIPE signal w. This prevents the server from receiving the SIGPIPE
	//signal if it tries to write to a socket whose peer has been closed; instead, the
	//write() fails with the error EPIPE
	if (signal(SIGPIPE, SIG_IGN) == SIG_ERR)
		return -1;
	setlocale(LC_ALL, "");

	if(!InitConfigFile("./AgentDaemon.conf"))
	{
		printf("Can't find config file");
		return -1;
	}
	Daemon::Instance("./AgentDaemon.conf")->StartDaemon(argc, argv, argv[0], 60);

	Log::Init(Daemon::Instance()->config);
	// Log::Init("AgentDaemon.conf");
	// Log::set_prog_name(argv[0]);
	// Log::set_mod_name("AgentEngineDaemon");

	//set global string value from config file 
	Log::Info("start to AgentEngine::instance()->run()");
	AgentEngine::instance()->run();

	return 0;

}
#endif 

