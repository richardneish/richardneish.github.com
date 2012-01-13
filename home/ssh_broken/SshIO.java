/**
 * SshIO 
 * --
 * 
 * This file implments the SSH prtotocol 1.5
 *
 * The protocol version used in this document is SSH protocol version 1.5.
 * This file is part of "The Java Ssh Applet".
 */



/**		EVOLUTION
 * 18/07/98 all of the bugs seem removed :-)
 * 09/07/98 last bug : OutOfMemoryError : 
 *			Thrown when the Java Virtual Machine cannot allocate an object because it is out 
 *			of memory, and no more memory could be made available by the garbage collector. 
 * 09/07/98 MD5 hash of the Server key provided for authentification
 * 15/06/98 problem of CRC solved (in fact some times a packet was discarded)
 * 12/06/98 I really have a problem with crc errors. I get check sum errors too often??
 * 11/06/98	Problem solved by using the cryptix in the pgp applet (they modified the package !!!)
 *			can't use the applet in the "sandbox" 
 * 06/06/98 I am back after some delighting exams !!!
 * 25/03/98 begining of the work on the display part.
 * 23/03/98 crc error !!: the first packet received after the SSH_CMSG_EXEC_SHELL message has a bad crc value !!! error from the server ??? after, everything is ok !
 * 20/03/98 pty
 * 17/03/98 Login & password accepted (we have solved every problem concerning encryption :-) )
 * 17/03/98 Problem in decrypting with IDEA 
 * 15/03/98 RSA problem solved with Fab (what a great supervisor ... )
 * 12/03/98 Mail to the Computer maintenance of SSH on thor : /var/adm/messages (log file)
 * 6/03/98  Design of the RSA/PKCS#1
 *			When we send SSH_CMSG_SESSION_KEY, the socket is closed by the server ( Fin-Wait-2 )
 * 1/01/98  CRC problem solved with the SSH sources (C++)
 * 27/02/98 Progress report (Problem with the CRC computation)
 *	
 */


import java.net.Socket;
import java.io.BufferedInputStream;
import java.io.BufferedOutputStream;
import java.io.IOException;

//import java.security.SecureRandom; //not supported by netscape

import cryptix.crypt.MD5; 

import SshTerm;
import SshPacket;
import SshCrypto;



class SshIO 
{

	/**
	 * variables for the connection
	 */
	private String identification_string = ""; //("SSH-<protocolmajor>.<protocolminor>-<version>\n")
	private String identification_string_sent = "SSH-1.5-Java Ssh 1.1 (01/08/98) developped by Cedric Gourio: javassh@france-mail.com\r";

	/**
	 * Debug level. This results in additional diagnostic messages on the
	 * java console.
	 */
	private static int debug = 0;

	/**
	 * State variable for Ssh negotiation reader
	 */

	
	private boolean encryption = false;
	private SshCrypto crypto;
	SshPacket lastPacketReceived;
	
					

	boolean ScreenUpdateForEachPacket = false;
	

	private String login, password; 
		//nobody is to access those fields  : better to use pivate, nobody knows :-)


	public SshTerm term; //intinialized by Ssh.class, used to know the display settings...

	public String dataToSend = null;
	
	public String hashHostKey = null;										// equals to the applet parameter if any .
	

	byte lastPacketSentType;


   
	// phase : handleBytes
	private int phase = 0;
	private final int PHASE_INIT =					0;
	private final int PHASE_SSH_RECEIVE_PACKET =	1;
	

		
	//handlePacket
	//messages
    //  The supported packet types and the corresponding message numbers are
	//	given in the following table.  Messages with _MSG_ in their name may
	//	be sent by either side.  Messages with _CMSG_ are only sent by the
	//  client, and messages with _SMSG_ only by the server.
	//
	private final int SSH_MSG_DISCONNECT =			1;
	private final int SSH_SMSG_PUBLIC_KEY =			2;
	private final int SSH_CMSG_SESSION_KEY =		3;	
	private final int SSH_CMSG_USER =				4;
	private final int SSH_CMSG_AUTH_PASSWORD =		9;
	private final int SSH_CMSG_REQUEST_PTY =		10;
	private final int SSH_CMSG_EXEC_SHELL =			12;
	private final int SSH_SMSG_SUCCESS =			14;
	private final int SSH_SMSG_FAILURE =			15;
	private final int SSH_CMSG_STDIN_DATA =			16;
	private final int SSH_SMSG_STDOUT_DATA =		17;
	private final int SSH_SMSG_STDERR_DATA =		18;
	private final int SSH_SMSG_EXITSTATUS =			20;
	private final int SSH_CMSG_EXIT_CONFIRMATION =	33;

	//used in getPacket
	private int position = 0;				// used to know, how far we are in packet_length_array[], padding[] ...


	 //
	 // encryption types
	 //
       private int SSH_CIPHER_NONE = 0;	 // No encryption						//not supported on thor
       private int SSH_CIPHER_IDEA = 1;  // IDEA in CFB mode				//Supported on thor, iplemented !
       private int SSH_CIPHER_DES  = 2;  // DES in CBC mode						//not supported on thor
       private int SSH_CIPHER_3DES = 3;  // Triple-DES in CBC mode			//Supported on thor, not implemented !
       private int SSH_CIPHER_TSS  = 4;  // An experimental stream cipher		//not supported on thor
       private int SSH_CIPHER_RC4  = 5;  // RC4


		
	 //	
	 // authentication methods 
	 //
	   private final int SSH_AUTH_RHOSTS =		1;   //.rhosts or /etc/hosts.equiv
	   private final int SSH_AUTH_RSA =			2;   //pure RSA authentication
	   private final int SSH_AUTH_PASSWORD =	3;   //password authentication, implemented !
	   private final int SSH_AUTH_RHOSTS_RSA =  4;   //.rhosts with RSA host authentication



	private Socket socket;
	private BufferedInputStream is;
	private BufferedOutputStream os;



	/**
	 * Initialise SshIO
	 * @param user name : login 
	 * @param password
	 */
	public SshIO(String l, String p) {
		password = p;
		login = l;
	}



	
	/**
	 * Connect to the remote host at the specified port.
	 * @param address the symbolic host address
	 * @param port the numeric port
	 * @see #disconnect
	 */
	public void connect(String address, int port) throws IOException {
		if(debug > 0) System.out.println("Ssh.connect("+address+","+port+")");
		socket = new Socket(address, port);
		is = new BufferedInputStream(socket.getInputStream());
		os = new BufferedOutputStream(socket.getOutputStream());
		if(debug > 0) System.out.println("connection OK");
		SshPacket.encryption = encryption = false;

		//The maximum length of a packet
        //(not including the length field and padding) is 262144 bytes.
	}

	/**
	 * Disconnect from remote host.
	 * @see #connect
	 */
	public void disconnect() {
		
		if(debug > 0) System.out.println("SshIO.disconnect()");
		if(socket !=null) 
			try { 
				socket.close();
			}
			catch(Exception e) {
				System.err.println("SshIO: disconnection problem");
				e.printStackTrace();
			socket=null; 
		}
	}


	/**
	 * Read data from the remote host. Blocks until data is available. 
	 * 
	 * Returns an array of bytes that will be displayed.
	 * 
	 */
	synchronized public byte[] receive() throws IOException {
		
		if (socket==null) throw new IOException("Connection closed.");
		
		byte buff[]=null;
		int start = 0;

		if (lastPacketReceived!=null) if (lastPacketReceived.toBeFinished) {
			buff = lastPacketReceived.unfinishedBuffer;
			start = lastPacketReceived.positionInUnfinishedBuffer;
		}		 
		if (buff==null) {
			int count = is.available();
	 		if (count==0) return null;
			buff = new byte[count];
			count = is.read(buff,0, buff.length);
			if(count < 0) throw new IOException("Connection closed.");
			if (count<=buff.length) {
				byte[] newBuff = new byte[count];
				for(int i=0; i<count; i++) newBuff[i] = buff[i];
				buff = newBuff;
			}
			if(debug > 0) 
				System.out.println("SshIO.receive(): read bytes: " + count + "\n");
		}
		SshPacket packet =  handleBytes(buff, start, buff.length);
		if (packet!=null) { //a complete packet has been received
			lastPacketReceived = packet;
			byte result[] =	handlePacket(lastPacketReceived.getType(), lastPacketReceived.getData());
	
			if (!ScreenUpdateForEachPacket) 
				if (lastPacketReceived.toBeFinished)
					return SshMisc.addArrayOfBytes(result, receive());
			return result;
		}
		return null;
	}


	
	//
	// Send data to the remote host.
	
	private void send(byte[] buf) throws IOException {
		if(debug > 1) System.out.println("SshIO.send(" + buf + ", 1" + buf.length + ")" );
		os.write(buf);
		os.flush();
	}

	private void send(byte b) throws IOException {
		if(debug > 1) System.out.println("SshIO.send(" + b + ", 1" + ")" );
		os.write(b);
		os.flush();
	}


	synchronized public void sendData(String str) throws IOException {
		if(debug > 1) System.out.println( "SshIO.send(" + str + ")" );
		if (dataToSend==null) dataToSend = str;
		else dataToSend += str;
		Send_SSH_CMSG_STDIN_DATA(str);
		dataToSend = null;
	}
	


	private SshPacket handleBytes(byte buff[], int offset, int count) throws IOException {
		
		if(debug > 1) 
			System.out.println("SshIO.getPacket(" + buff + "," + count + ")" );
		
		byte b;  			// of course, byte is a signed entity (-128 -> 127)
		int boffset = offset;	// offset in the buffer received 
	
		
		while(boffset < count) {

			b=buff[boffset++];	
			
			switch(phase) {

			case PHASE_INIT: 
				// both sides MUST send an identification string of the form 
				// "SSH-protoversion-softwareversion comments", 
				// followed by newline character(ascii 10 = '\n' or '\r') 				
				//
				identification_string += (char) b;
				if (b=='\n') { 
					phase++; 
					byte[] StringByte = new byte[identification_string_sent.length()];
					identification_string_sent.getBytes(0, identification_string_sent.length(),	StringByte, 0);
					send(StringByte);
					
					position = 0;
					//return StringByte;
					byte[] data = SshMisc.createString(identification_string_sent);
					byte packet_type = SSH_SMSG_STDOUT_DATA;
					SshPacket fistLine = createPacket(packet_type, data);
					//identification_string = null;
					//identification_string_sent = null;
					return fistLine;

				}
				break;

			
			case PHASE_SSH_RECEIVE_PACKET:
				
					SshPacket result = lastPacketReceived.getPacketfromBytes(buff, boffset-1, count);
					return 	result;
		

			} // switch(phase) 
		
		} 	//while(boffset < count) 
		
	
		return null;
	}//getPacket
		



	//
	// Create a packet 
	//

	private SshPacket createPacket(byte newType, byte[] newData) throws IOException { 
		return new SshPacket(newType, newData);

		

	} //createPacket




	
	private byte[] handlePacket(byte packetType, byte[] packetData) throws IOException { //the message to handle is data and its length is 


		//if(debug > 1) w
			//System.out.println("SshIO.handlePacket("+data+","+ (packet_length - 5) +")");
		
			byte b;  			// of course, byte is a signed entity (-128 -> 127)
			int boffset = 0;	//offset in the buffer received 

			//we have to deal with data....
			
			if(debug > 0) 
				System.out.println("1 packet to handle");

			switch(packetType) {


 
			case SSH_MSG_DISCONNECT:

				String str = SshMisc.getString(boffset, packetData);
				byte[] StrByte = new byte[str.length()];
				str.getBytes(0, str.length(),	StrByte, 0);
				disconnect();
				return StrByte;
	

		
			case SSH_SMSG_PUBLIC_KEY:
				
				byte[] anti_spoofing_cookie = new  byte[8];				//8 bytes
				byte[] server_key_bits = new  byte[4];					//32-bit int
				byte[] server_key_public_exponent;						//mp-int
				byte[] server_key_public_modulus;						//mp-int
				byte[] host_key_bits = new  byte[4];					//32-bit int
				byte[] host_key_public_exponent;						//mp-int
				byte[] host_key_public_modulus;							//mp-int
				byte[] protocol_flags = new  byte[4];					//32-bit int
				byte[] supported_ciphers_mask = new  byte[4];			//32-bit int
				byte[] supported_authentications_mask = new  byte[4];	//32-bit int
			
				for(int i=0;i<=7;i++) anti_spoofing_cookie[i] = packetData[boffset++];
				for(int i=0;i<=3;i++) server_key_bits[i] = packetData[boffset++];
				server_key_public_exponent = SshMisc.getMpInt(boffset,packetData); //boffset is not modified :-(
				boffset += server_key_public_exponent.length+2;
				server_key_public_modulus = SshMisc.getMpInt(boffset,packetData);
				boffset += server_key_public_modulus.length+2;
				for(int i=0;i<=3;i++) host_key_bits[i] = packetData[boffset++];
				host_key_public_exponent = SshMisc.getMpInt(boffset,packetData);
				boffset += host_key_public_exponent.length+2;
				host_key_public_modulus = SshMisc.getMpInt(boffset,packetData); // boffset can not be modified (Java = crap Language)
				boffset += host_key_public_modulus.length+2;
				for(int i=0;i<4;i++) protocol_flags[i] = packetData[boffset++];
				for(int i=0;i<4;i++) supported_ciphers_mask[i] = packetData[boffset++];
				for(int i=0;i<4;i++) supported_authentications_mask[i] = packetData[boffset++];

				// we have completely received the PUBLIC_KEY
				// we prepare the answer ...

			 	Send_SSH_CMSG_SESSION_KEY(	anti_spoofing_cookie,
											server_key_public_modulus,
											host_key_public_modulus,
											supported_ciphers_mask,
											server_key_public_exponent,
											host_key_public_exponent);
				
				// we check if MD5(server_key_public_exponent) is equals to the applet parameter if any .
				if (hashHostKey!=null && hashHostKey.compareTo("")!=0) {

					// we compute hashHostKeyBis the hash value in hexa of host_key_public_modulus
					byte[] Md5_hostKey = MD5.hash(host_key_public_modulus);
					String hashHostKeyBis = "";
					for(int i=0; i < Md5_hostKey.length; i++) {
						String hex = "";
						int[] v = new int[2];
						v[0] = (Md5_hostKey[i]&240)>>4;
						v[1] = (Md5_hostKey[i]&15);
						for (int j=0; j<1; j++)
							switch (v[j]) {
									case 10 : hex +="a"; break;
									case 11 : hex +="b"; break;
									case 12 : hex +="c"; break;
									case 13 : hex +="d"; break;
									case 14 : hex +="e"; break;
									case 15 : hex +="f"; break;
									default : hex +=String.valueOf(v[j]);break;
							}
						hashHostKeyBis = hashHostKeyBis + hex;
					}
					//we compare the 2 values
					if (hashHostKeyBis.compareTo(hashHostKey)!=0) {
						login = password = "";
						str = new String("\nHash value of the host key not correct \r\nlogin & password have been reset \r\n - erase the 'hashHostKey' parameter in the Html\r\n (it is used for auhentificating the server and prevent you from connecting \r\n  to any other)\r\n");

						StrByte = new byte[str.length()];
						str.getBytes(0, str.length(),	StrByte, 0);
						return(StrByte);
					}
				}

				break;

		

				
			case SSH_SMSG_SUCCESS:
			

				if (lastPacketSentType==SSH_CMSG_SESSION_KEY) { //we have succefully sent the session key !! (at last :-) )
					Send_SSH_CMSG_USER();
					break;
				}

				if (lastPacketSentType==SSH_CMSG_AUTH_PASSWORD) {// password correct !!!
					//yahoo
					System.out.println("login succesful");


					//now we have to start the interactive session ...
		 			Send_SSH_CMSG_REQUEST_PTY(); //request a pseudo-terminal
					str = new String("\nLogin & password accepted\r\n");
					StrByte = new byte[str.length()];
					str.getBytes(0, str.length(),	StrByte, 0);
					return(StrByte);
					}

				if (lastPacketSentType==SSH_CMSG_REQUEST_PTY) {// pty accepted !!
					Send_SSH_CMSG_EXEC_SHELL(); //we start a shell
					break;
				}

				break;


			case SSH_SMSG_FAILURE:
				
				if (lastPacketSentType==SSH_CMSG_AUTH_PASSWORD) {// password incorrect ???
					System.out.println("failed to log");
					str = new String("\nLogin & password not accepted\r\n");
					disconnect();
					StrByte = new byte[str.length()];
					str.getBytes(0, str.length(),	StrByte, 0);
					return(StrByte);
				}
				if (lastPacketSentType==SSH_CMSG_USER) { // authentication is needed for the given user (in most cases that's true)
					Send_SSH_CMSG_AUTH_PASSWORD();
					break;
				}

				if (lastPacketSentType==SSH_CMSG_REQUEST_PTY) {// pty not accepted !!
					break;
				}
				break;




			case SSH_SMSG_STDOUT_DATA: //receive some data from the server

				str = SshMisc.getString(0, packetData);
				StrByte = new byte[str.length()];
				str.getBytes(0, str.length(),	StrByte, 0);
				return(StrByte);

			case SSH_SMSG_STDERR_DATA: //receive some error data from the server

			//	if(debug > 1) 
				str = "Error : " + SshMisc.getString(0, packetData);
				System.out.println("SshIO.handlePacket : " + "STDERR_DATA " + str );
					//StrByte = new byte[str.length()];
					//str.getBytes(0, str.length(),	StrByte, 0);
					//return(StrByte);
				break;
			
			case SSH_SMSG_EXITSTATUS: //sent by the server to indicate that the client program has terminated.
				
				//32-bit int   exit status of the command
				int value = (packetData[0]<<24)+(packetData[1]<<16)+(packetData[2]<<8)+(packetData[3]);
				Send_SSH_CMSG_EXIT_CONFIRMATION();
				System.out.println("SshIO : Exit status " + value );
				disconnect();
				break;


			default: 
				System.err.print("SshIO.handlePacket : Packet Type unknown\r\n");
				break;
		
			}//	switch(b)
			return null;
	} // handlePacket






	private void sendPacket(SshPacket packet) throws IOException {  
		send(packet.getBytes()); 
		lastPacketSentType = packet.getType();
	} 




	 // 
	 // Send_SSH_CMSG_SESSION_KEY
	 // Create : 
	 // the session_id, 
	 // the session_key, 
	 // the Xored session_key, 
	 // the double_encrypted session key
	 // send SSH_CMSG_SESSION_KEY
	 // Turn the encryption on (initialise the block cipher)
	 //

	private byte[] Send_SSH_CMSG_SESSION_KEY(	byte[] anti_spoofing_cookie,
												byte[] server_key_public_modulus,
												byte[] host_key_public_modulus,
												byte[] supported_ciphers_mask,
												byte[] server_key_public_exponent,
												byte[] host_key_public_exponent

												) throws IOException { 
	
		
		String str;
		byte[] StrByte ;
		int boffset;
		
		String session_id;						//
		byte cipher_type;						//encryption types
		byte[] session_key;						//mp-int
	
		

	    // create the session id
		//	session_id = MD5(hostkey->n || servkey->n || cookie) //protocol V 1.5. (we use this one)
		//	session_id = MD5(servkey->n || hostkey->n || cookie) //protocol V 1.1.(Why is it different ??)
		//
		session_id = new String(host_key_public_modulus,0);
		session_id += new String(server_key_public_modulus, 0);
		session_id += new String(anti_spoofing_cookie,0);
		
		byte[] hash_md5 = MD5.hash(session_id); 
		

		byte[] session_id_byte = new byte[session_id.length()];
		session_id.getBytes(0, session_id.length(),	session_id_byte, 0);
		

	    //	SSH_CMSG_SESSION_KEY : Sent by the client
	    //	    1 byte       cipher_type (must be one of the supported values)
		// 	    8 bytes      anti_spoofing_cookie (must match data sent by the server)
		//	    mp-int       double-encrypted session key (uses the session-id)
		//	    32-bit int   protocol_flags
		//
		cipher_type = (byte) SSH_CIPHER_IDEA;	// SSH_CIPHER_NONE	SSH_CIPHER_IDEA;
		if (  (((1 << cipher_type) & 0xff) & supported_ciphers_mask[3])==0) { 
			System.err.println("SshIO: encryption method not supported\n");
			disconnect();
			str = new String("\rencryption method not supported !!\r\n");
			StrByte = new byte[str.length()];
			str.getBytes(0, str.length(),	StrByte, 0);
			return(StrByte);
		}
	

		// 	anti_spoofing_cookie : the same 
	    //      double_encrypted_session_key :
		//		32 bytes of random bits
		//		Xor the 16 first bytes with the session-id
		//		encrypt with the server_key_public (small) then the host_key_public(big) using RSA.
		//
		
		//32 bytes of random bits
		byte[] random_bits1 = new byte[16], random_bits2 = new byte[16];
		

		java.util.Date date = new java.util.Date(); ////the number of milliseconds since January 1, 1970, 00:00:00 GMT. 
		//Math.random()   a pseudorandom double between 0.0 and 1.0. 
		random_bits2 = random_bits1 =MD5.hash(	"" + Math.random()*date.getDate());
		random_bits1 = MD5.hash(SshMisc.addArrayOfBytes(MD5.hash(password+login), random_bits1));
		random_bits2 = MD5.hash(SshMisc.addArrayOfBytes(MD5.hash(password+login), random_bits2));

		// SecureRandom random = new java.security.SecureRandom(random_bits1); //no supported by netscape :-(
		// random.nextBytes(random_bits1);
		// random.nextBytes(random_bits2);
			
		session_key  = SshMisc.addArrayOfBytes(random_bits1,random_bits2);

		//Xor the 16 first bytes with the session-id
		byte[] session_keyXored  = SshMisc.XORArrayOfBytes(random_bits1,hash_md5);
		session_keyXored = SshMisc.addArrayOfBytes(session_keyXored, session_keyXored);

		//We encrypt now!!
		byte[] encrypted_session_key = SshCrypto.encrypteRSAPkcs1Twice(	session_keyXored,
																		server_key_public_exponent,
																		server_key_public_modulus,
																		host_key_public_exponent,
																		host_key_public_modulus);

		//	protocol_flags :protocol extension   cf. page 18
		byte[] protocol_flags = new  byte[4];						//32-bit int
		protocol_flags [0] = protocol_flags [1] = protocol_flags [2] = protocol_flags [3]  = 0;
		

		//set the data
		int length = 1 + //cipher_type
				anti_spoofing_cookie.length + 
				encrypted_session_key.length +
				protocol_flags.length;


		byte[] data = new byte[length];
		boffset = 0;
		data[boffset++] = (byte) cipher_type;
		for (int i=0; i<8; i++) data[boffset++] = anti_spoofing_cookie[i];
		for (int i=0; i<encrypted_session_key.length; i++) data[boffset++] = encrypted_session_key[i];
		for (int i=0; i<4; i++) data[boffset++] = protocol_flags[i];
		
		//set the packet_type
		byte packet_type = SSH_CMSG_SESSION_KEY;
		SshPacket packet = createPacket(packet_type, data);
		sendPacket(packet);

		if (cipher_type == (byte) SSH_CIPHER_IDEA) {
			byte[] IDEAKey = new byte[16];
			for(int i=0; i<16; i++) IDEAKey[i] = session_key[i];
			crypto = new SshCrypto(IDEAKey);
			encryption=true;
			SshPacket.encryption = encryption;
			SshPacket.crypto = crypto;
		}
	return null;
	} //Send_SSH_CMSG_SESSION_KEY






   /**
    * SSH_CMSG_USER
	* string   user login name on server
	*/
	private byte[] Send_SSH_CMSG_USER() throws IOException {
		byte[] data = SshMisc.createString(login);
		byte packet_type = SSH_CMSG_USER;
		SshPacket packet = createPacket(packet_type, data);
		sendPacket(packet);
		return null;
	} //Send_SSH_CMSG_USER



   /**
    * Send_SSH_CMSG_AUTH_PASSWORD
	* string   user password
	*/
	private byte[] Send_SSH_CMSG_AUTH_PASSWORD() throws IOException {
		byte[] data = SshMisc.createString(password); 
		byte packet_type = SSH_CMSG_AUTH_PASSWORD;
		SshPacket packet = createPacket(packet_type, data);
		sendPacket(packet);
		return null;
	} //Send_SSH_CMSG_AUTH_PASSWORD



   /**
    * Send_SSH_CMSG_EXEC_SHELL
	*  (no arguments)
	*   Starts a shell (command interpreter), and enters interactive
    *   session mode.
	*/
	private byte[] Send_SSH_CMSG_EXEC_SHELL() throws IOException {
		
		byte[] data = null;
		byte packet_type = SSH_CMSG_EXEC_SHELL;
		SshPacket packet = createPacket(packet_type, data);
		sendPacket(packet);
		lastPacketSentType = packet_type;
		return null;
	} //Send_SSH_CMSG_EXEC_SHELL


   /**
    * Send_SSH_CMSG_STDIN_DATA
	*  
	*/
	private byte[] Send_SSH_CMSG_STDIN_DATA(String str) throws IOException {
		byte[] data = SshMisc.createString(str);
		byte packet_type = SSH_CMSG_STDIN_DATA;
		SshPacket packet = createPacket(packet_type, data);
		sendPacket(packet);
		return null;
	} //Send_SSH_CMSG_STDIN_DATA


	
   /**
    * Send_SSH_CMSG_REQUEST_PTY
	*   string       TERM environment variable value (e.g. vt100)
    *   32-bit int   terminal height, rows (e.g., 24)
    *   32-bit int   terminal width, columns (e.g., 80)
    *   32-bit int   terminal width, pixels (0 if no graphics) (e.g., 480)
	*/
	private byte[] Send_SSH_CMSG_REQUEST_PTY() throws IOException {

		byte[] termType = SshMisc.createString(term.getTerminalType());//termType);
		byte[] row = new byte[4];
		row[3] = (byte) (term.getSize().height);//getSize().height);
		byte[] col = new byte[4];
		col[3] = (byte) term.getSize().width;//getSize().;
		byte[] XPixels = new byte[4];
		//XPixels[2] = (byte)(480/256);
		//XPixels[3] = (byte)(480%256);
		byte[] YPixels = new byte[4];
		//YPixels[2] = (byte)(640/256);
		//YPixels[3] = (byte)(640%256);	
		byte[] terminalModes = new byte[1];
		terminalModes[0] =  0;

		byte [] data = new byte[termType.length + 4*4 + terminalModes.length];
		int offset = 0;
		for (int i=0; i<termType.length; i++) data[offset++] = termType[i];
		for (int i=0; i<4; i++) data[offset++] = row[i];
		for (int i=0; i<4; i++) data[offset++] = col[i];
		for (int i=0; i<4; i++) data[offset++] = XPixels[i];
		for (int i=0; i<4; i++) data[offset++] = YPixels[i];
		for (int i=0; i<terminalModes.length; i++) data[offset++] = terminalModes[i];

		byte packet_type =  SSH_CMSG_REQUEST_PTY;

		SshPacket packet = createPacket(packet_type, data);
		sendPacket(packet);
		return null;
	} //Send_SSH_CMSG_REQUEST_PTY





	private byte[] Send_SSH_CMSG_EXIT_CONFIRMATION() throws IOException {
		byte packet_type = SSH_CMSG_EXIT_CONFIRMATION;
		SshPacket packet = createPacket(packet_type, null);
		sendPacket(packet);
		return null;
	}














	


  


}// class SshIO


					
		