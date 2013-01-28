#include <dirent.h>
#include <sys/types.h>
#include <sys/wait.h>
#include <string>
#include<iostream>
using namespace std;
/*
REF: http://tools.ietf.org/html/rfc3986#section-2.3
http://en.wikipedia.org/wiki/Percent-encoding
*/
int UrlEncode(const string src, string *dst)
{
	static char encodeMap[256] = 
	{
		0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 0-15
		0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 16-31
		'+', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '-', '.', 0,  // 32-47
		'0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 0, 0, 0, 0, 0, 0,  // 48-63
		0, 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',   // 64-79
		'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 0, 0, 0, 0, '_',  // 80-95
		0, 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o',  // 96-111
		'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 0, 0, 0, '~', 0,  // 112-127
		0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 128-143
		0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 144-159
		0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 160-175
		0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 176-191
		0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 192-207
		0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 208-223
		0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 224-239
		0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 240-255
	};
	dst->clear();
	for(int i = 0; i < src.length(); ++i)
	{
		unsigned char c = src[i];
                cout << "orig char: " << c ;
		if(encodeMap[c])
		{
                        cout << "con: " << encodeMap[c] << endl;
			dst->push_back(encodeMap[c]);
		}
		else
		{
			dst->push_back('%');
			char low, high;
			low = c&0x0f;
			high = c >> 4;
                 
			// convert the low and high to hex 0-16
			if(high > 9)
                        {
			     dst->push_back(high + 'A' - 10);
                             cout << "high: " << high + 'A' - 10;
                        }
			else
                        {
			    dst->push_back(high + '0');
                            cout << "high: " <<  low + '0';
                        }
			if(low > 9)
                       { 
				dst->push_back(low + 'A' - 10);
                            cout << "low: " << low + 'A' - 10 << endl;
                        }
			else
                        {
				dst->push_back(low + '0');
                            cout << "low: " << low + '0' << endl;
                        }

		}
	}
	return 0;
}

int main(int argc, char **argv)
{
    string test = "测试持续集成任务";
    cout << test << endl;
    string encoded;
    UrlEncode(test, &encoded);
    cout << encoded << endl;
}
