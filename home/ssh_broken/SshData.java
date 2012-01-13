//******************************************************************************
// SshData.java
//
// This file is part of "The Java Ssh Applet".
//******************************************************************************

/**
 * SshData
 * --
 * 
 *  This class provides mutual exclusion on a strings accessed by two threads.
 *  Two intances of this class are used in the Secure Shell Project
 *	
 *	SsfData.string is the information
 *	The information is accessed by the functions Get and Put.
 *	A guard is used to control the access.
 * 
 * This file is part of "The Java Ssh Applet".
 */

public class SshData {

	private static int debug = 0;

	private String data = null;

	private boolean ticket = false,
					askTicket = true,
					releaseTicket = false;



	synchronized private boolean guard(boolean value) throws InterruptedException{
		if (value==askTicket && ticket==false) ticket = true; //the ticket is asked and is available
		if (value==releaseTicket) ticket = false;
		notify();
		return ticket;
	}


	public void put(String str) {
		if (str==null)  return;
		try {
			while (!guard(askTicket)) wait() ; //we wait to get a ticket to pass
		}
		catch (InterruptedException e) {
			ticket = false;
			return;
		};
		if(debug > 0) System.out.println("SshData::put(" + data + ")\n" );
		if (data == null) data = str;
		else data += str;
		try {
			guard(releaseTicket); //we release the ticket
		}
		catch (InterruptedException e) {};
	}



	public String get() {
		
		try {
			while (!guard(askTicket)) wait(); //we wait to get a ticket to pass
		}
		catch (InterruptedException e) {
			ticket = false;
			return null;
		};
		
		if (data==null) return null;
		String str = data;
		data = null;
		if(debug > 0) System.out.println("SshData::return :" + data + "\n" );
		try {
			guard(releaseTicket); //we release the ticket
		}
		catch (InterruptedException e) {};
		return str;
	}
}
