/******************************************************************************
 * 
 * SshTerm - a VT100 implementation 
 * --
 *
 * This class provides a VT100 emulation
 * 
 * A partial listing of the VT100 control set is given at the end of the file
 * For the display we used a simple CharDisplay class
 *
 * This file is part of "The Java Ssh Applet".
 */
 
   

/**		EVOLUTION  
 * 	
 * This file is part of "The Java Ssh Applet".
 *
 *	17/07/98	Everything seems ok :-)
 *	12/06/98 :	We are using  display.CharDisplay.class for display characters
 *  28/04/98 :	The programme works but I have to work on the terminal !!!
 *	11/02/98 :	Start 
 */


import java.awt.Panel;
import java.awt.Dimension;
import java.awt.Event;

import display.CharDisplay;

import SshData;


class SshTerm extends Panel {

	private static int debug = 0;

	public int numberOfCharsDisplayBeforeScreenUpdate;
	
	//public String dataToSend = null;
	SshData dataToSend = null;


	//display
	private CharDisplay terminalScreen;
	final private String termType = "vt100";
	private Dimension size = new Dimension(80,25);  // rows and columns 
 	private int cursorX = 0, cursorY = 0;			// current cursor position
	private int  attributes  = 0;					// Display Attributes (color, bold , blink ...)
    private int  insertmode  = 0;					// In the prog, insertmode is always 1  
		
	



	private final static char ESC = 27;
	private final static char DEL = 8; 
	private final static char BELL = 7; 

	


	private int tState; // Terminal State
		private final static int TSTATE_DATA  = 0;
		private final static int TSTATE_ESC  = 1; // ESC 
		private final static int TSTATE_CSI  = 2; // ESC [ //Control Sequence Introducer

	
	private int [] arg = new int[10]; 
	private int current = 1;



	//constructor
	public SshTerm(String ScrollbarPosition) { 
		terminalScreen = new CharDisplay(size.width, size.height);
		terminalScreen.setResizeStrategy(CharDisplay.RESIZE_FONT);
		attributes = 0;
		if(ScrollbarPosition.compareTo("East") == 0) terminalScreen.setScrollbar("East");
		if(ScrollbarPosition.compareTo("West") == 0) terminalScreen.setScrollbar("West");
		terminalScreen.setBorder(2, false);
		add(terminalScreen);
		dataToSend = new SshData();
	}



	public boolean send(String s) {				// sent to the terminal
		
		putString(s);
		return true;
	}
	
	public boolean sendHost(String str) {		// send to the server 

		/*if (dataToSend!=null) dataToSend += str;
		else dataToSend = str;*/

		dataToSend.put(str);
		return true;
	}

	


	public String getTerminalType() {	//vt100 : (will be send by SshIO)
		return termType;
	}

	public Dimension getSize() {		//numbers of rows and columns (will be send by SshIO)
		return size;
		//terminalScreen.getSize();  is not valid !!!  (wrong value)
	}

	public void setBufferSize(int bufferSize) {
		terminalScreen.setBufferSize(bufferSize);
	}


	public void clear() {
		terminalScreen.deleteArea(0, 0, size.width , size.height);
		attributes = 0;
		tState = TSTATE_DATA;
		terminalScreen.setCursorPos(cursorX = 0, cursorY = 0);
		//terminalScreen.insertLine(size.height,size.height);
		terminalScreen.redraw();	
	}



	public void putString(String str) { 
		
		if (str==null) return;
		if (cursorY < 0) cursorY = 0;
		if (cursorY > size.height) cursorY = size.height;
	    terminalScreen.markLine(cursorY,1);
		
		int length = str.length();
		if (numberOfCharsDisplayBeforeScreenUpdate<=0) numberOfCharsDisplayBeforeScreenUpdate = length;

		for (int i=0; i<length; i++) {
			putChar(str.charAt(i));
			//if (i % numberOfCharsDisplayBeforeScreenUpdate == 0) 
				//terminalScreen.redraw(); //we redraw every numberOfCharsDisplayBeforeScreenUpdate chracters
		}
	    terminalScreen.setCursorPos(cursorX, cursorY);
		terminalScreen.redraw();
	}



	public void  putChar(char c) { 


//VT100 : <ESC>[ unknown :c: 99
//ESC +  unknown :\: 92
//ESC +  unknown :Z: 90
//VT100 : <ESC>[ unknown :c: 99


		
		switch (tState) {

			case TSTATE_DATA : 
				switch (c) {
					case ESC :		
						tState = TSTATE_ESC;
 						if (debug>0) System.out.print("\nterminal : ESC ");
						break;

					case DEL :
						if (debug>0) System.out.print("" + c);
						cursorX--;
						break; //backspace is reprensented by 8, 32, 8 : 
							//that's what we get by pressing the del key.

					case BELL :
						System.out.print("(bip)");
						break; 				

					case '\r' :		
						if (cursorY >= size.height) {
							terminalScreen.insertLine(cursorY,1);
							cursorY--;
						}
						cursorX = 0;
						if (debug>0) System.out.print("r"+c);
						break;
					
					case '\n' :
						cursorY++;
						if (debug>0) System.out.print("n"+c);
						break;
				
					default :		
						if (c>255) {
							if (debug>0) System.out.println("char > 255:"+((int)c));
							break;
						}
						if (c<32) {
							if (debug>0) System.out.println("char < 32:"+((int)c));
							break;
						}
						if (cursorX >= size.width) {
							if (cursorY < size.height - 1) cursorY++;
							else terminalScreen.insertLine(cursorY,terminalScreen.SCROLL_UP);
							cursorX = 0;
						}
						if (cursorY >= size.height) {
							terminalScreen.insertLine(cursorY,1);
							cursorY--;
						}
						if (cursorY < 0) cursorY = 0;
						if (cursorX < 0) cursorX = 0;
						if (insertmode==1) {
							terminalScreen.insertChar(cursorX, cursorY, c, attributes);
						} else {
							terminalScreen.putChar(cursorX, cursorY, c, attributes);
						}
						if (debug>0) System.out.print(""+c);
						cursorX++;
						break;
				} // switch (c) 
				break; // case TSTATE_DATA 
			
			case TSTATE_ESC : switch (c) {
 						
				case ESC :	
					tState = TSTATE_ESC; //we should not get this character, ??
					if (debug>0) System.out.print("ESC");
					break;
				case '[' : 	
					tState = TSTATE_CSI;
					if (debug>0) System.out.print(c);
					arg[0]= 0;
					current = 1;
					break;
				case '(' : // Set default font. : we do nothing ( one font is enough ..)
				case ')' : // Set alternate font. : we do nothing
					tState = TSTATE_DATA;
					if (debug>0) System.out.print(c);
					arg[0]= 0;
					current = 1;
					break;
				default :	
					System.out.println("VT100 : <ESC> +  unknown :" + c + ": " + (int) c);
					tState = TSTATE_DATA;
					break;
					 } //switch (c) 
				break;// case TSTATE_ESC :
			
			case TSTATE_CSI : 
				switch (c) {	// ESC [ 
					case '0' : 	
					case '1' : 	
					case '2' : 	
					case '3' : 	
					case '4' : 	
					case '5' : 	
					case '6' : 	
					case '7' : 	
					case '8' : 	
					case '9' : 	
						arg[0] = 10*arg[0] + (c - '0');
						if (debug>0) System.out.print(c - '0');
						break;
					case ';' : 	
						arg[current++] = arg[0];//current = number of variable + 1
						if (current>=arg.length) current = arg.length - 1; //if too many arguments we drop some
						arg[0] = 0;
						if (debug>0) System.out.print(";");
						break;
// Cursor Control
	// Force Cursor Position - Cursor Home	<ESC>[{ROW};{COLUMN}H
					case 'H' :
					case 'f' :	
						if (debug>0) System.out.println(""+c);
						if (current==2)  { //2 variable //Force Cursor Position	<ESC>[{ROW};{COLUMN}f
							current = 1;
							arg[2] = arg[0];
							arg[0] = 0;
							cursorX = arg[2] - 1; 
							cursorY = arg[1] -1;
							tState = TSTATE_DATA;
							break;
						}
						if (current==1)  { //0 variable 
							cursorX = 0; cursorY = 0;
							tState = TSTATE_DATA;
							current = 1; 
							arg[0] = 0;
							break;
						}
						break;

									
	//Cursor Up		<ESC>[{COUNT}A						
					case 'A' :
						if (debug>0) System.out.println(""+c);
						if (arg[0]==0) arg[0] = 1;
						cursorY -= arg[0];
						tState = TSTATE_DATA;
						current = 1; arg[0] = 0;
						break;
										

	//Cursor Down		<ESC>[{COUNT}B						
					case 'B' : 		
						if (debug>0) System.out.println(""+c);
						if (arg[0]==0) arg[0] = 1;
						cursorY += arg[0];
						tState = TSTATE_DATA;
						current = 1; arg[0] = 0;
						break;
										

	//Cursor Forward		<ESC>[{COUNT}C						
					case 'C' : 		
						if (debug>0) System.out.println(""+c);
						if (arg[0]==0) arg[0] = 1;
						cursorX += arg[0];
						tState = TSTATE_DATA;
						current = 1; arg[0] = 0;
						break;


// scrolling
	//Scroll Down		<ESC>D		Scroll display down one line. 
					case 'D' : 	
						if (debug>0) System.out.println(""+c);
						if (cursorY == terminalScreen.getTopMargin() -1 ||
							cursorY == terminalScreen.getBottomMargin()	||
							cursorY == size.height-1 )
								terminalScreen.insertLine(cursorY,1,terminalScreen.SCROLL_UP);
						else cursorY++; 
						arg[0] = 0;
						tState = TSTATE_DATA;
						break;
				


	//Scroll Up			<ESC>M		Scroll display up one line. 
					case 'M' : 	
						if (debug>0) System.out.println(""+c);
						if ((cursorY>=terminalScreen.getTopMargin()) && (cursorY<=terminalScreen.getBottomMargin())) // in scrolregion
							terminalScreen.insertLine(cursorY,1,terminalScreen.SCROLL_DOWN);
							// else do nothing ; 
							arg[0] = 0;
							tState = TSTATE_DATA;
						break;






	//Scroll Screen		<ESC>[r					Enable scrolling for entire display. 
	//Scroll Screen		<ESC>[{start};{end}r	Enable scrolling from row {start} to row {end}. 
					case 'r' : 		
						if (debug>0) System.out.println(""+c);
						arg[2] = arg[0];
						if (arg[0]==0 && current == 1) {
							arg[1] = 1;
							arg[2] = size.height;
						}
						terminalScreen.setTopMargin(arg[1]-1);
						terminalScreen.setBottomMargin(arg[2]-1);
						tState = TSTATE_DATA;
						current = 1; arg[0] = 0;
						break;

// Erasing Text
										
	//	Erase End of Line	<ESC>[K		Erases from the current cursor position to the end of the current line. 
	// 	Erase Start of Line	<ESC>[1K	Erases from the current cursor position to the start of the current line. 
	//	Erase Line			<ESC>[2K	Erases the entire current line. 


					case 'K' : 		
						if (debug>0) System.out.println("K");
						switch(arg[0]) {
							case 0 : /*clear to right*/
								if (cursorX < size.width - 1)
									terminalScreen.deleteArea(cursorX,cursorY,size.width - cursorX,1);
								tState = TSTATE_DATA;
								break;
							case 1 : /*clear to the left*/
								if (cursorX > 0)
									terminalScreen.deleteArea(0,cursorY,cursorX,1);
								tState = TSTATE_DATA;
								break;
							case 2 : /*clear whole line */
								terminalScreen.deleteArea(0,cursorY,size.width,1);
								tState = TSTATE_DATA;
								break;
							default :	
								if (debug>0) System.out.println("?K");
									tState = TSTATE_DATA;
								break;
						}
						break; // case 'K'
											 
										
	//	Erase Down		<ESC>[J			Erases the screen from the current line down to the bottom of the screen. 
	//	Erase Up		<ESC>[1J		Erases the screen from the current line up to the top of the screen. 
	//	Erase Screen	<ESC>[2J		Erases the screen with the background color and moves the cursor to home. 
							
					case 'J' : 		 /* clear display.below current line */
						if (debug>0) System.out.println("J");
						switch(arg[0]) {
							case 0 : 
								if (cursorY < size.height-1)
									 terminalScreen.deleteArea(0, cursorY+1, size.width, size.height-cursorY-1);
								if (cursorX < size.width-1)
									terminalScreen.deleteArea(cursorX, cursorY, size.width - cursorX, 1);
								arg[0] = 0;
								tState = TSTATE_DATA;
								break;
							case 1 : 
								if (cursorY > 0)
									terminalScreen.deleteArea(0, 0, size.width, cursorY-1);
								if (cursorX > 0)
									terminalScreen.deleteArea(0, cursorY, cursorX, 1);
								terminalScreen.deleteArea(0,0,cursorX,cursorY);
								arg[0] = 0;
								tState = TSTATE_DATA;
								break;
							case 2 :
								terminalScreen.deleteArea(0,0,cursorX,cursorY);
								arg[0] = 0;
								tState = TSTATE_DATA;
								break;
							}
							break; //case 'J' 
	

			
	//Insert Line		<ESC>[{COUNT}L						
					case 'L' :
						if (debug>0) System.out.println(""+c);
						if (arg[0]==0) arg[0] = 1;
						terminalScreen.insertLine(cursorY,arg[0],terminalScreen.SCROLL_DOWN);
						tState = TSTATE_DATA;
						current = 1; arg[0] = 0;
						break;



											
//Set Display Attributes
	//color, bold , blink
						case 'm':	 //11
							arg[current] = arg[0];
							for (int i=1;i<=current;i++) { 
								if (debug>0) System.out.print(String.valueOf(arg[i])+";");
								switch (arg[i]) {
									case 0:
										attributes = 0;
										break;
									case 4:
										attributes |= CharDisplay.UNDERLINE;
										break;
									case 1:
										attributes |= CharDisplay.BOLD;
										break;
									case 7:
										attributes |= CharDisplay.INVERT;
										break;
									case 5: // blink on 
										break;
									case 25: /* blinking off */
										break;
									case 27:
										attributes &= ~CharDisplay.INVERT;
										break;
									case 24:
										attributes &= ~CharDisplay.UNDERLINE;
										break;
									case 22:
										attributes &= ~CharDisplay.BOLD;
										break;
									case 30:
									case 31:
									case 32:
									case 33:
									case 34:
									case 35:
									case 36:
									case 37:
										attributes &= ~(0xf<<3);
										attributes |= ((arg[i]-30)+1)<<3;
										break;
									case 39:
										attributes &= ~(0xf<<3);
										break;
									case 40:
									case 41:
									case 42:
									case 43:
									case 44:
									case 45:
									case 46:
									case 47:
										attributes &= ~(0xf<<7);
										attributes |= ((arg[i]-40)+1)<<7;
										break;
									case 49:
										attributes &= ~(0xf<<7);
										break;
									default: ////VT100 : <ESC>[ unknown :c: 99
										if (debug>0) System.out.print("?;");
										System.out.println( "VT100 : <ESC>[ unknown  :"
															+ arg[i]
															+ ": " 
															+ String.valueOf(arg[i]) 
															+ " m " );
										break;
								} //switch arg[i]
							} //for
							if (debug>0) System.out.println("m");
							tState = TSTATE_DATA;
							current = 1;
							arg[0] = 0;
							break; //case 'm'

						default :	
							System.err.println( "VT100 : <ESC>[ unknown :" + c + ": " + (int) c); 
							tState = TSTATE_DATA;
							break;
						} //	switch (c)
						break;//case TSTATE_CSI 

			default	:	
				System.err.println("VT100 : Unknown state"); // ??
				tState = TSTATE_DATA;
				break;
		} // switch (tState)
		terminalScreen.setCursorPos(cursorX,cursorY);
		terminalScreen.markLine(cursorY,1);
	} //putChar
 





	public boolean handleEvent(Event evt) {
  

		// handle keyboard events	
		
		if (evt.controlDown() && (evt.id == Event.KEY_ACTION && evt.key == Event.RIGHT ))  {
			return sendHost("\0"); //( CTRL+ -> )  we found it by luck :-)
		}
		
		if (evt.id == Event.KEY_PRESS && evt.key == '\r' ) {
			if (debug>0) System.out.println("\nterminal send() : r");
			return true; //weird
		}
		if (evt.id == Event.KEY_PRESS && evt.key == '\n' ) {
			return sendHost("\r"); //even more weird  
		}	

		if (evt.id == Event.KEY_PRESS) {
			if (debug>0) System.out.println("\nterminal send() : "+ (char)evt.key);
			if (evt.key!='\0') return sendHost(""+(char)evt.key ); //don(t send the first key for ê;ã ..
		}

		if (evt.id == Event.KEY_ACTION)
			switch (evt.key) {
				case Event.UP : return sendHost("\u001b[A"); // ESC [ A
				case Event.DOWN : return sendHost("\u001b[B"); // ESC [ B
				case Event.RIGHT : return sendHost("\u001b[C"); // ESC [ C
				case Event.LEFT : return sendHost("\u001b[D"); // ESC [ D					 
				default : return false;
			}
	
		return false;
	}






  
  	



}//end of class terminal



		




/*
The following is a partial listing of the VT100 control set. 

<ESC> represents the ANSI "escape" character, 0x1B. 
Bracketed tags represent modifiable decimal parameters; eg. {ROW} would be replaced by a row number. 



--------------------------------------------------------------------------------

Device Status
The following codes are used for reporting terminal/display settings, and vary depending on the implementation: 
Query Device Code	<ESC>[c
Requests a Report Device Code response from the device. 

Report Device Code	<ESC>[{code}0c
Generated by the device in response to Query Device Code request. 

Query Device Status	<ESC>[5n
Requests a Report Device Status response from the device. 

Report Device OK	<ESC>[0n
Generated by the device in response to a Query Device Status request; indicates that device is functioning correctly. 

Report Device Failure	<ESC>[3n
Generated by the device in response to a Query Device Status request; indicates that device is functioning improperly. 

Query Cursor Position	<ESC>[6n
Requests a Report Cursor Position response from the device. 

Report Cursor Position	<ESC>[{ROW};{COLUMN}R
Generated by the device in response to a Query Cursor Position request; reports current cursor position. 


--------------------------------------------------------------------------------

Terminal Setup
The h and l codes are used for setting terminal/display mode, and vary depending on the implementation. Line Wrap is one of the few setup codes that tend to be used consistently: 

Reset Device		<ESC>c
Reset all terminal settings to default. 

Enable Line Wrap	<ESC>[7h
Text wraps to next line if longer than the length of the display area. 

Disable Line Wrap	<ESC>[7l
Disables line wrapping. 


--------------------------------------------------------------------------------

Fonts
Some terminals support multiple fonts: normal/bold, swiss/italic, etc. There are a variety of special codes for certain terminals; the following are fairly standard: 

Font Set G0		<ESC>(
Set default font. 

Font Set G1		<ESC>)
Set alternate font. 


--------------------------------------------------------------------------------

Cursor Control
Cursor Home 		<ESC>[{ROW};{COLUMN}H
Sets the cursor position where subsequent text will begin. If no row/column parameters are provided (ie. <ESC>[H), the cursor will move to the home position, at the upper left of the screen. 

Cursor Up		<ESC>[{COUNT}A
Moves the cursor up by COUNT rows; the default count is 1. 

Cursor Down		<ESC>[{COUNT}B
Moves the cursor down by COUNT rows; the default count is 1. 

Cursor Forward		<ESC>[{COUNT}C
Moves the cursor forward by COUNT columns; the default count is 1. 

Force Cursor Position	<ESC>[{ROW};{COLUMN}f
Identical to Cursor Home. 

Save Cursor		<ESC>[s
Save current cursor position. 

Unsave Cursor		<ESC>[u
Restores cursor position after a Save Cursor. 

Save Cursor & Attrs	<ESC>7
Save current cursor position. 

Restore Cursor & Attrs	<ESC>8
Restores cursor position after a Save Cursor. 


--------------------------------------------------------------------------------


Scroll Screen		<ESC>[r
Enable scrolling for entire display. 

Scroll Screen		<ESC>[{start};{end}r
Enable scrolling from row {start} to row {end}. 

Scroll Down		<ESC>D
Scroll display down one line. 

Scroll Up		<ESC>M
Scroll display up one line. 


--------------------------------------------------------------------------------

Tab Control
Set Tab 		<ESC>H
Sets a tab at the current position. 

Clear Tab 		<ESC>[g
Clears tab at the current position. 

Clear All Tabs 		<ESC>[3g
Clears all tabs. 


--------------------------------------------------------------------------------

Erasing Text  ---- Completed !

Erase End of Line	<ESC>[K
Erases from the current cursor position to the end of the current line. 

Erase Start of Line	<ESC>[1K
Erases from the current cursor position to the start of the current line. 

Erase Line		<ESC>[2K
Erases the entire current line. 

Erase Down		<ESC>[J
Erases the screen from the current line down to the bottom of the screen. 

Erase Up		<ESC>[1J
Erases the screen from the current line up to the top of the screen. 

Erase Screen		<ESC>[2J
Erases the screen with the background color and moves the cursor to home. 


--------------------------------------------------------------------------------

Printing
Some terminals support local printing: 
Print Screen		<ESC>[i
Print the current screen. 

Print Line		<ESC>[1i
Print the current line. 

Stop Print Log		<ESC>[4i
Disable log. 

Start Print Log		<ESC>[5i
Start log; all received text is echoed to a printer. 


--------------------------------------------------------------------------------

Define Key
Set Key Definition	<ESC>[{key};"{string}"p
Associates a string of text to a keyboard key. {key} indicates the key by its ASCII value in decimal. 


--------------------------------------------------------------------------------

Set Display Attributes
Set Attribute Mode	<ESC>[{attr1};...;{attrn}m
Sets multiple display attribute settings. The following lists standard attributes: 
0	Reset all attributes
1	Bright
2	Dim
4	Underscore	
5	Blink
7	Reverse
8	Hidden

	Foreground Colors
30	Black
31	Red
32	Green
33	Yellow
34	Blue
35	Magenta
36	Cyan
37	White

	Background Colors
40	Black
41	Red
42	Green
43	Yellow
44	Blue
45	Magenta
46	Cyan
47	White
*/

  







