//******************************************************************************
// Ssh.java:	Applet
//
//  20/07/98 : the program is finished :-)
//	28/04/98 : the programme works but I have to work on the terminal !!!
//	11/02/98 : Start
//******************************************************************************


import java.applet.Applet;

import java.awt.Panel;
import java.awt.TextField;
import java.awt.Button;
import java.awt.Label;
import java.awt.BorderLayout;
import java.awt.GridBagLayout ;
import java.awt.GridLayout;
import java.awt.Graphics;
import java.awt.Color;
import java.awt.Font;
import java.awt.Event;

import java.io.IOException;

import SshIO;
import SshTerm;
import Bulb;
import SshData;


//==============================================================================
// Classe Main de l'applet Ssh
//
//==============================================================================

public class Ssh extends Applet implements Runnable
{



	/**
	 * Debug level. This results in additional diagnostic messages on the
	 * java console.
	 */
	private static int debug = 0;
	
 	private TextField hostFld = new TextField(15),
			loginFld = new TextField(8),
	        passwordFld = new TextField(8);

	private Bulb bulb = null;

	private Button connectBut = new Button("Connect"),
	       disconnectBut = new Button("Disconnect");
	
	private Label statusLbl = new Label("", Label.CENTER);
	
  
	static protected SshTerm term;
   


  /**
   * The Ssh io methods.
   * @see socket.SshIO
   */ 
   protected SshIO tio = null;
 

  /**
   * the Hostname (thor.cam.ac.uk) - the port number (22).- the login (cag31) - the password (***)
   */
	protected String	hostname = null,			//cf init
						hostnameFromParameter = null, 
						login =  "",
						password = "";


 	protected int port = 22;


	private SshData dataToSend = null,
					dataToPrint = null; 

	
	// some state variables;
	private boolean localecho = false;//true;
	private boolean connected = false;
 	
	
 
	//the thread t will send and receive data
	private Thread	threadIO = null, 
					threadTerm = null;

	private boolean threadTermAlreadyStarted = false;

  
  
	// Constructeur de classes Ssh
	//--------------------------------------------------------------------------
	public Ssh() {}

	public String getAppletInfo()
	{
		return "Nom : Ssh\r\n" +
		       "Auteur: Cedric : cedric.gourio@france-mail.com\r\n" +
		       "Diploma Project 1998 - Cambridge";
	}


	
	public void init()
	{

	
//we set each parameter

		String paramTemp = null;
	
		
		paramTemp = getParameter("hostname");
		if(paramTemp != null) hostname = paramTemp;
		else hostname=getCodeBase().getHost(); //host where is the applet
		hostFld.setText(hostname);	
		hostnameFromParameter = new String(hostname);

		
		paramTemp = getParameter("port");
		if(paramTemp != null) port = Integer.valueOf(paramTemp).intValue();	

		
		paramTemp = getParameter("login");
		if(paramTemp != null) login = paramTemp;
		loginFld.setText(login);	


		paramTemp = getParameter("password");		//does it make sense ??
		if(paramTemp!= null) password = paramTemp;
		passwordFld.setEchoCharacter((char)'*');
		passwordFld.setText(password);
	
		paramTemp = getParameter("Title");
		if(paramTemp == null) paramTemp = " Secure Shell Applet";
		String titleStr = new String(paramTemp);


		
		bulb = new Bulb( 
				getImage(getCodeBase(), "./Images/red.jpg"),
				getImage(getCodeBase(), "./Images/yellow.jpg")
			   );
	
		

//color		
		setBackground(Color.lightGray);



//layouts ...
		setLayout(new BorderLayout());

		Label title = new Label(titleStr, Label.CENTER);
		title.setFont(new Font("Helvetica", Font.BOLD, 20));
		add(title,"North");

		Panel menuTop = new Panel();
		menuTop.setLayout(	//new FlowLayout());
							//GridLayout(1,8));
							new GridBagLayout());
	
	
		Label hostLbl = new Label("Host:",Label.RIGHT);
		Label loginLbl = new Label("Login:",Label.RIGHT);
		Label PasswordLbl = new Label("Password:",Label.RIGHT);

				
		menuTop.add(hostLbl);
		menuTop.add(hostFld);
		menuTop.add(loginLbl);
		menuTop.add(loginFld);
		menuTop.add(PasswordLbl);
		menuTop.add(passwordFld);
		menuTop.add(connectBut);
		menuTop.add(disconnectBut);
		menuTop.add(bulb);


		Panel menuBottom = new Panel();
		Panel menu	= new Panel();
		menu.setLayout(new GridLayout(2,1));
		menu.add(menuTop);
		menu.add(menuBottom);
		add(menu,"South");
		
		
			
		
		paramTemp = getParameter("scrollbar"); //Scrollbar position 
	
		try {
    		term = new SshTerm(paramTemp); 
			add(term,"Center");
		} catch(Exception e) {
			System.err.println("telnet: cannot load terminal ");
			e.printStackTrace();
		}
		paramTemp = getParameter("ScrollingbufferSize");
		if(paramTemp != null) {
			term.setBufferSize(Integer.valueOf(paramTemp).intValue());	
			System.out.println("Buffer size: " + paramTemp);
		}
		paramTemp = getParameter("numberOfCharsDisplayBeforeScreenUpdate");
		if(paramTemp != null) {
			term.numberOfCharsDisplayBeforeScreenUpdate = Integer.valueOf(paramTemp).intValue();	
			System.out.println("Number of chars displayed before screen update: " + paramTemp);
		}
	
}

 

	
	public void destroy() {}

	public void paint(Graphics g) {	}

	public void start() {}

	public void stop() {}

	
	public void run() {
		
		
		
		//Terminal Thread
		if (!threadTermAlreadyStarted) { //this will be the terminal Thread
			threadTermAlreadyStarted = true;
			System.out.println("terminal OK");
			while(dataToPrint==null);
			while(term != null) { 
				//to print
				String str = dataToPrint.get();
				if (str!=null) {
					term.putString(str);	// put the received string on the terminal
					if(debug > 0) System.out.println("Ssh::run()->term.putSring(" + str +")\n");
				}
				//to send
				dataToSend.put(term.dataToSend.get());
				
				try { 
					Thread.sleep(100);
				}
				catch( InterruptedException e) {};
			}
			System.out.println("terminal OFF");
			return;
		}
			


		System.out.println("Connection successful");
		bulb.connected(true);
			
		//SshIO Thread
		while(threadIO != null) { 
			try {
				//sent
				String str = dataToSend.get(); //block !!
				if (str!=null) {
					tio.sendData(str);
					if(debug > 0) System.out.println("Ssh::run()->tio.sendData(" + str +")");
				}
				//received
				byte[] receivedBytes = tio.receive();
				if(receivedBytes!=null) dataToPrint.put(new String( receivedBytes,0));
			} 
			catch(IOException e) {
				threadIO=null;
			}
		}
		//cleanup
		connected = false;
		tio =null;
		bulb.connected(false);
		threadTermAlreadyStarted = true;
		//dataToPrint.put(null); //threadTermAlreadyStarted may be blocked
		System.out.println("Disconnection successful");
	
		
		
		
	}


 /**
   * Connect to the specified host and port
   * @param host destination host address
   * @param prt destination hosts port
   * 
   *
   */
	synchronized public boolean connect(String host, int prt) {
	
		
		if(debug > 0) System.out.println("SshIO::connect()");
		
		hostname = host; port = prt;
		String login = loginFld.getText();	
		String password = passwordFld.getText();

		if(hostname == null || hostname.length() == 0) return false;

		if(threadIO != null ) {
			//System.err.println("Ssh: connect: existing connection preserved");
			return false;
		} 
		

		tio = new SshIO(login, password);
		tio.term = this.term;		//the SshIO will send the characteristics of the diplay
			
		String paramTemp = getParameter("ScreenUpdateForEachPacket");
		if(paramTemp != null) {
			tio.ScreenUpdateForEachPacket = (paramTemp.compareTo("true")==0);	
			System.out.println("ScreenUpdateForEachPacket : " + tio.ScreenUpdateForEachPacket);
		}
			
		
		
		tio.hashHostKey = getParameter("hashHostKey");
	
		if (tio.hashHostKey!=null) if (hostnameFromParameter!=null) {	//the parameters hostname & hashHostKey are not empty 
																	
				if (host.compareTo(tio.hashHostKey) !=0) tio.hashHostKey = null; 
				//the user has changed the host so no way to authenticate the server
		}
		
		
		
		try { 
			tio.connect(hostname, port);
		}
		catch(IOException e) {
			term.putString("Failed to connect.\r\n");
			connected = false;
			tio = null;
		    System.err.println("Ssh: failed to connect to "+hostname+" "+port);
			e.printStackTrace();
			return false;
		}
		

	
		
		if (threadTerm==null) {
			threadTermAlreadyStarted = false;
			threadTerm = new Thread(this);
			//threadTerm.setPriority(Thread.MAX_PRIORITY);
			threadTerm.start();

			dataToSend = new SshData();
			dataToPrint = new SshData(); 
	}
		
		while (!threadTermAlreadyStarted);
		
		threadIO = new Thread(this);
		//threadIO.setPriority(Thread.MAX_PRIORITY);
		threadIO.start();					
	
		passwordFld.setText("");
		term.clear();
		return true;
	}




 /**
   * Disconnect from the remote host.
   * @return false if there was a problem disconnecting.
   */
	synchronized public boolean disconnect() {	
		//can't set tio and term to null because the thread t may acces them
	 
		if(debug > 0) System.out.println("SshIO.disconnect()");

		threadIO = null; //no more thread

		if(tio == null) {
			System.err.println("Ssh.diconnect : no connection");
			return false;
		}
    
		tio.disconnect(); 
		
		connected = false; 

		return true;
	}

 

 
	public boolean action(Event evt, Object o) {
		boolean ret = false;
		if(evt.target == connectBut ) {//&& evt.id == Event.MOUSE_DOWN ) {	//we connect
			String.valueOf(evt.x);
			hostname = hostFld.getText();	
			connect(hostname, port);
			term.requestFocus();
			ret = true;
		}
		if(evt.target == disconnectBut ){ //&& evt.id == Event.MOUSE_DOWN ) {
			disconnect();
			ret = true;
		}

		if(evt.target == passwordFld && evt.id == Event.KEY_PRESS && evt.key == 10) {	//we connect after the password is set
			hostname = hostFld.getText();	
			connect(hostname, port);
			term.requestFocus();
			ret = true;
		}

		return ret;
	}


	
	public boolean handleEvent(Event evt) {
  
		if (action(evt,evt.target)) return true;
		
		return false;
	
	}

} //end of Ssh class
