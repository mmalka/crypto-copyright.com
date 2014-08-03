#include <windows.h>
#include <Shlobj.h>
#include <time.h>

#include <fstream>
#include <iostream>
#include "SHA3.h"

#define DEFAULT_DIGEST_BITS 256
#define FILE_BUFFER_BYTES 4096

OPENFILENAME ofn ;
char szFile[200] ;
HFONT font=CreateFont(-17,0,0,0,FW_NORMAL,0,0,0,DEFAULT_CHARSET,OUT_DEFAULT_PRECIS,CLIP_DEFAULT_PRECIS,DEFAULT_QUALITY,FF_DONTCARE,"Calibri");

char *hexDigestForFile( const char *filename, const int digestBytes ){
    clock_t tic1, toc1;

    std::ifstream file;
    char buffer[FILE_BUFFER_BYTES];

    SHA3 sha3( digestBytes );
    HashFunction *hash = &sha3;
    FILE *f;
    size_t n;

	if ( ( f = fopen ( filename, "rb" ) ) == NULL )
		printf("Error opening file\n");

    tic1 = clock();
	while ( ( n = fread ( buffer, 1 , sizeof(buffer), f ) ) > 0 )
    {
        for( int i = 0; i < n; i++ )
        {
                hash->hash( (int) ((unsigned char) buffer[i]) );
        }

    }

	fclose(f);

    toc1 = clock();
    printf("Hashing function Took: %f seconds\n", (double)(toc1 - tic1) / CLOCKS_PER_SEC);

    unsigned char *digest = new unsigned char[digestBytes];
    char *hexDigest = new char[2*digestBytes + 1];
    char *hexLookup = (char*)"0123456789abcdef";
    hexDigest[2*digestBytes] = '\0';
    hash->digest( digest );

    for( int byte = 0; byte < digestBytes; byte++ ){
        hexDigest[2*byte]   = hexLookup[digest[byte] >> 4];
        hexDigest[(2*byte)+1] = hexLookup[digest[byte] & 15];
    }
    delete( digest );
    return hexDigest;
}

// Declare a HWND (Window Handle) for every control you want to use in your application.
// In this tutorial, a button and a text field are used. The window itself also has a window handle.
HWND button, hwnd, editHwnd, editHwnd2, editHwnd3, button2;

// This method is the Window Procedure. The window procedure handles messages sent to the window. A message
// can be sent from another application or from the OS itself to initiate an action in the application.
LRESULT CALLBACK WndProc(HWND hwnd, UINT msg, WPARAM wParam, LPARAM lParam)
{
	// This switch block differentiates between the message type that could have been received. If you want to
	// handle a specific type of message in your application, just define it in this block.
    switch(msg)
    {
		// This message type is used by the OS to close a window. Just closes the window using DestroyWindow(hwnd);
        case WM_CLOSE:
            DestroyWindow(hwnd);
        break;
		// This message type is part of the WM_CLOSE case. After the DestroyWindow(hwnd) function is called, a
		// WM_DESTROY message is sent to the window, which actually closes it.
        case WM_DESTROY:
            PostQuitMessage(0);
        break;
		// This message type is an important one for GUI programming. It symbolizes an event for a button for example.
		case WM_COMMAND:
			// To differentiate between controls, compare the HWND of, for example, the button to the HWND that is passed
			// into the LPARAM parameter. This way you can establish control-specific actions.
			if (lParam == (LPARAM)button && wParam == BN_CLICKED)
			{
				// The button was clicked, this is your proof.
				SendMessage(editHwnd3, WM_SETTEXT, NULL, (LPARAM)"");
				TCHAR buff[1024];
				GetWindowText(editHwnd, buff, 1024);

                char *message;
                int digestSize;
                message = buff;
                digestSize = 224;
                std::cout << "Performing SHA3-" << digestSize << " on: '" << message << "'" << std::endl;

                SHA3 sha3( digestSize/8 );
                sha3.hashString( message );
                char *hexDigest = sha3.digestInHex();
                std::cout << hexDigest << std::endl;
                SendMessage(editHwnd2, WM_SETTEXT, NULL, (LPARAM)hexDigest);
			}

			else if (lParam == (LPARAM)button2 && wParam == BN_CLICKED)
			{
				// The button was clicked, this is your proof.
				// open a file name
                ZeroMemory( &ofn , sizeof( ofn));
                ofn.lStructSize = sizeof ( ofn );
                ofn.hwndOwner = NULL  ;
                ofn.lpstrFile = szFile ;
                ofn.lpstrFile[0] = '\0';
                ofn.nMaxFile = sizeof( szFile );
                ofn.lpstrFilter = "All\0*.*\0Text\0*.TXT\0";
                ofn.nFilterIndex =1;
                ofn.lpstrFileTitle = NULL ;
                ofn.nMaxFileTitle = 0 ;
                ofn.lpstrInitialDir=NULL ;
                ofn.Flags = OFN_PATHMUSTEXIST|OFN_FILEMUSTEXIST ;

                if ( GetOpenFileNameA( &ofn ) != 0 ) {
                    // all ok
                }
                else {
                    SendMessage(editHwnd2, WM_SETTEXT, NULL, (LPARAM)"");
                    SendMessage(editHwnd3, WM_SETTEXT, NULL, (LPARAM)"");
                    break;
                }

                char *filename = ofn.lpstrFile;
                int digestSize = 224;

                char *hexDigest = hexDigestForFile( filename, digestSize/8 );
                if( hexDigest != 0 ){
                    std::cout << hexDigest << "\t" << filename << std::endl;
                }
                else{
                    std::cout << "Couldn't open file: " << filename << std::endl;
                }

                SendMessage(editHwnd3, WM_SETTEXT, NULL, (LPARAM)filename);
                SendMessage(editHwnd2, WM_SETTEXT, NULL, (LPARAM)hexDigest);
                delete( hexDigest );
			}

		break;
        default:
        // When no message type is handled in your application, return the default window procedure. In this case the message
        // will be handled elsewhere or not handled at all.
        return DefWindowProc(hwnd, msg, wParam, lParam);
    }
    return 0;
}

// This function is the entrypoint of your application. Consider it the main function. The code in this function will be executed
// at first.
int WINAPI WinMain(HINSTANCE hInstance, HINSTANCE hPrevInstance, LPSTR lpCmdLine, int nCmdShow)
{
    INITCOMMONCONTROLSEX icc;

    // Initialise common controls.
    icc.dwSize = sizeof(icc);
    icc.dwICC = ICC_WIN95_CLASSES;
    InitCommonControlsEx(&icc);

	// In order to be able to create a window you need to have a window class available. A window class can be created for your
	// application by registering one. The following struct declaration and fill provides details for a new window class.
    WNDCLASSEX wc;

    wc.cbSize        = sizeof(WNDCLASSEX);
    wc.style         = 0;
    wc.lpfnWndProc   = WndProc;
    wc.cbClsExtra    = 0;
    wc.cbWndExtra    = 0;
    wc.hInstance     = hInstance;
    wc.hIcon         = LoadIcon(hInstance, MAKEINTRESOURCE(100));
    wc.hCursor       = LoadCursor(hInstance, IDC_ARROW);
    wc.hbrBackground = (HBRUSH)(COLOR_WINDOW+1);
    wc.lpszMenuName  = NULL;
    wc.lpszClassName = "Crypto-Copyright";
    wc.hIconSm       = NULL;

	// This function actually registers the window class. If the information specified in the 'wc' struct is correct,
	// the window class should be created and no error is returned.
    if(!RegisterClassEx(&wc))
    {
        return 0;
    }

    // This function creates the first window. It uses the window class registered in the first part, and takes a title,
	// style and position/size parameters. For more information about style-specific definitions, refer to the MSDN where
	// extended documentation is available.
    hwnd = CreateWindowExA(WS_EX_CLIENTEDGE, "Crypto-Copyright", "Crypto-Copyright.com SHA-3/224 Hasher ",
        (WS_OVERLAPPED | WS_CAPTION | WS_SYSMENU | WS_MINIMIZEBOX),
        CW_USEDEFAULT, CW_USEDEFAULT, 630, 270, NULL, NULL, hInstance, NULL);

    HBRUSH brush = CreateSolidBrush(RGB(240, 240, 240));
    SetClassLongPtr(hwnd, GCLP_HBRBACKGROUND, (LONG)brush);

	// This function creates the button that is displayed on the window. It takes almost the same parameter types as the function
	// that created the window. A thing to note here though, is BS_DEFPUSHBUTTON, and BUTTON as window class, which is an existing one.
	button = CreateWindowA("BUTTON", "Hash", (WS_VISIBLE | WS_CHILD | BS_DEFPUSHBUTTON)
		, 475, 8, 120, 30, hwnd, NULL, hInstance, NULL);

    button2 = CreateWindowA("BUTTON", "Browse", (WS_VISIBLE | WS_CHILD | BS_DEFPUSHBUTTON)
		, 475, 164, 120, 30, hwnd, NULL, hInstance, NULL);

	// This function creates the text field that is displayed on the window. It is almost the same as the function that created the
	// button. Note the EDIT as window class, which is an existing window class defining a "text field".
	editHwnd = CreateWindowA("EDIT", NULL, WS_CHILD | WS_VISIBLE | WS_BORDER | ES_AUTOVSCROLL | ES_MULTILINE | WS_VSCROLL, 10, 8, 450, 150, hwnd, NULL, hInstance, NULL);

    editHwnd2 = CreateWindowA("EDIT", NULL, WS_CHILD | WS_VISIBLE | WS_BORDER | ES_READONLY, 10, 200, 450, 25, hwnd, NULL, hInstance, NULL);
    editHwnd3 = CreateWindowA("EDIT", NULL, WS_CHILD | WS_VISIBLE | WS_BORDER | ES_READONLY, 10, 167, 450, 25, hwnd, NULL, hInstance, NULL);

	// In Win32 you need SendMessage for a lot of GUI functionality and altering. The purpose of this function is very wide and not
	// explainable in this tutorial. Refer to MSDN for specific information about a use of this function. In this case it is used to
	// set a text value into the text field created on the window.
	SendMessage(editHwnd, WM_SETTEXT, NULL, (LPARAM)"Insert Text to Hash here");
	SendMessage(editHwnd,WM_SETFONT,(WPARAM)font,MAKELPARAM(true,0));

	// This block checks the integrity of the created window and it's controls. If a control did not succeed creation, the window
	// is not created succesfully, hence it should not be shown.
    if(!hwnd || !button || !editHwnd)
    {
        return 0;
    }

	// Everything went right, show the window including all controls.
    ShowWindow(hwnd, nCmdShow);
    UpdateWindow(hwnd);

	// This part is the "message loop". This loop ensures the application keeps running and makes the window able to receive messages
	// in the WndProc function. You must have this piece of code in your GUI application if you want it to run properly.
	MSG Msg;
    while(GetMessage(&Msg, NULL, 0, 0) > 0)
    {
        TranslateMessage(&Msg);
        DispatchMessage(&Msg);
    }

    return 0;
}
